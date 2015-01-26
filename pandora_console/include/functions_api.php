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

global $config;

//Set character encoding to UTF-8 - fixes a lot of multibyte character headaches

require_once('functions_agents.php');
require_once('functions_modules.php');
include_once($config['homedir'] . "/include/functions_profile.php");
include_once($config['homedir'] . "/include/functions.php");
include_once($config['homedir'] . "/include/functions_ui.php");
include_once($config['homedir'] . "/include/functions_graph.php");
include_once($config['homedir'] . "/include/functions_events.php");
include_once($config['homedir'] . "/include/functions_groups.php");
include_once($config['homedir'] . "/include/functions_network_components.php");
include_once($config['homedir'] . "/include/functions_netflow.php");
include_once($config['homedir'] . "/include/functions_servers.php");
enterprise_include_once ('include/functions_local_components.php');
enterprise_include_once ('include/functions_events.php');

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
function returnError($typeError, $returnType = 'string') {
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
			returnData("string",
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
			if ($data['type'] == 'string') {
				echo $data['data'];
			}
			else {
				//TODO
			}
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
									echo str_replace("\n", " ", $dataContent[$index]);
								if (end($data['list_index']) == $index)
									echo "\n";
								else
									echo $separator;
							}
						}
					}
					else {
						if (!empty($data['data'])) {
							
							foreach ($data['data'] as $dataContent) {
								
								$clean = array_map("array_apply_io_safe_output", $dataContent);
								
								foreach ($clean as $k => $v) {
									$clean[$k] = str_replace("\r", "\n", $clean[$k]);
									$clean[$k] = str_replace("\n", " ", $clean[$k]);
									$clean[$k] = strip_tags($clean[$k]);
									$clean[$k] = str_replace(';',' ',$clean[$k]);
								}
								$row = implode($separator, $clean);
								
								echo $row . "\n";
							}
						}
					}
					break;
				case 'string':
					echo $data['data'];
					break;
			}
			break;
		case 'json':
			$data = array_apply_io_safe_output($data);
			header('Content-type: application/json');
			echo json_encode ($data);
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
		if (preg_match('/\*/', $acl_ip)) {
			
			// Scape for protection
			$acl_ip = str_replace('.','\.',$acl_ip);
			
			// Replace wilcard by .* to do efective in regular expression
			$acl_ip = str_replace('*','.*',$acl_ip);
			
			// If the string match with the beginning of the IP give it access
			if (preg_match('/'.$acl_ip.'/', $ip)) {
				return true;
			}
		}
	}
	
	return false;
}

// Return string OK,[version],[build]
function api_get_test() {
	global $pandora_version;
	global $build_version;
	
	echo "OK,$pandora_version,$build_version";
	
	if (defined ('METACONSOLE')) {
		echo ",meta";
	}
}

// Returs the string OK if a connection to the event replication DB can be established.
function api_get_test_event_replication_db() {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$status = enterprise_hook('events_test_replication_db', array());
	if ($status === ENTERPRISE_NOT_HOOK) {
		echo 'ERR';
		return;
	}
	echo $status;
}

//-------------------------DEFINED OPERATIONS FUNCTIONS-----------------
function api_get_groups($thrash1, $thrash2, $other, $returnType, $user_in_db) {
	if (defined ('METACONSOLE')) {
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

function api_get_agent_module_name_last_value($agentName, $moduleName, $other = ';', $returnType)
{
	global $config;
	
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
		api_get_module_last_value($idModuleAgent, null, $other, $returnType);
	}
}

function api_get_module_last_value($idAgentModule, $trash1, $other = ';', $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$sql = sprintf('SELECT datos
		FROM tagente_estado
		WHERE id_agente_modulo = %d', $idAgentModule);
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
function api_get_tree_agents($trash1, $trahs2, $other, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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
	
	/** NOTE: if you want to add an output field, you have to add it to;
		1. $master_fields (field name)
		2. one of following field_column_mapping array (a pair of field name and corresponding column designation) 
		
		e.g. To add a new field named 'agent_NEWFIELD' that comes from tagente's COLUMN_X , you have to add;
		1. "agent_NEW_FIELD"  to $master_fields
		2. "'agent_NEW_FIELD' => 'agent_NEWFIELD as COLUMN_X'"  to $agent_field_column_mapping
		**/
	
	/* all of output field names */
	$master_fields = array(
		'type_row',
		
		'group_id',
		'group_name',
		'group_parent',
		'disabled',
		'custom_id',
		'group_description',
		'group_contact',
		'group_other',
		
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
		'module_max_retries',
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
		'module_plugin_macros',
		'module_macros',
		
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
		'alert_actions_id_group');
	
	/* agent related field mappings (output field => column designation for 'tagente') */
	
	$agent_field_column_mapping = array(
		/* agent_id is not in this list (because it is mandatory) */
		/* agent_id_group is not in this list  */
		'agent_name' => 'nombre as agent_name',
		'agent_direction' => 'direccion as agent_direction',
		'agent_comentary' => 'comentarios as agent_comentary',
		'agent_last_contant' => 'ultimo_contacto as agent_last_contant',
		'agent_mode' => 'modo as agent_mode',
		'agent_interval' => 'intervalo as agent_interval',
		'agent_id_os' => 'id_os as agent_id_os',
		'agent_os_version' => 'os_version as agent_os_version',
		'agent_version' => 'agent_version as agent_version',
		'agent_last_remote_contact' => 'ultimo_contacto_remoto as agent_last_remote_contact',
		'agent_disabled' => 'disabled as agent_disabled',
		'agent_id_parent' => 'id_parent as agent_id_parent',
		'agent_custom_id' => 'custom_id as agent_custom_id',
		'agent_server_name' => 'server_name as agent_server_name',
		'agent_cascade_protection' => 'cascade_protection as agent_cascade_protection');
	
	/* module related field mappings 1/2 (output field => column for 'tagente_modulo') */
	
	$module_field_column_mampping = array(
		/* 'module_id_agent_modulo (id_agente_modulo) is not in this list */
		'module_id_agent' => 'id_agente as module_id_agent',
		'module_id_module_type' => 'id_tipo_modulo as module_id_module_type',
		'module_description' => 'descripcion as module_description',
		'module_name' => 'nombre as module_name',
		'module_max' => 'max as module_max',
		'module_min' => 'min as module_min',
		'module_interval' => 'module_interval',
		'module_tcp_port' => 'tcp_port as module_tcp_port',
		'module_tcp_send' => 'tcp_send as module_tcp_send',
		'module_tcp_rcv' => 'tcp_rcv as module_tcp_rcv',
		'module_snmp_community' => 'snmp_community as module_snmp_community',
		'module_snmp_oid' => 'snmp_oid as module_snmp_oid',
		'module_ip_target' => 'ip_target as module_ip_target',
		'module_id_module_group' => 'id_module_group as module_id_module_group',
		'module_flag' => 'flag as module_flag',
		'module_id_module' => 'id_modulo as module_id_module',
		'module_disabled' => 'disabled as module_disabled',
		'module_id_export' => 'id_export as module_id_export',
		'module_plugin_user' => 'plugin_user as module_plugin_user',
		'module_plugin_pass' => 'plugin_pass as module_plugin_pass',
		'module_plugin_parameter' => 'plugin_parameter as module_plugin_parameter',
		'module_id_plugin' => 'id_plugin as module_id_plugin',
		'module_post_process' => 'post_process as module_post_process',
		'module_prediction_module' => 'prediction_module as module_prediction_module',
		'module_max_timeout' => 'max_timeout as module_max_timeout',
		'module_max_retries' => 'max_retries as module_max_retries',
		'module_custom_id' => 'custom_id as module_custom_id',
		'module_history_data' => 'history_data as module_history_data',
		'module_min_warning' => 'min_warning as module_min_warning',
		'module_max_warning' => 'max_warning as module_max_warning',
		'module_str_warning' => 'str_warning as module_str_warning',
		'module_min_critical' => 'min_critical as module_min_critical',
		'module_max_critical' => 'max_critical as module_max_critical',
		'module_str_critical' => 'str_critical as module_str_critical',
		'module_min_ff_event' => 'min_ff_event as module_min_ff_event',
		'module_delete_pending' => 'delete_pending as module_delete_pending',
		'module_plugin_macros' => 'macros as module_plugin_macros',
		'module_macros' => 'module_macros as module_macros');
	
	/* module related field mappings 2/2 (output field => column for 'tagente_estado') */
	
	$estado_fields_to_columns_mapping = array(
		/* 'module_id_agent_modulo (id_agente_modulo) is not in this list */
		'module_id_agent_state' => 'id_agente_estado as module_id_agent_state',
		'module_data' => 'datos as module_data',
		'module_timestamp' => 'timestamp as module_timestamp',
		'module_state' => 'estado as module_state',
		'module_last_try' => 'last_try as module_last_try',
		'module_utimestamp' => 'utimestamp as module_utimestamp',
		'module_current_interval' => 'current_interval as module_current_interval',
		'module_running_by' => 'running_by as module_running_by',
		'module_last_execution_try' => 'last_execution_try as module_last_execution_try',
		'module_status_changes' => 'status_changes as module_status_changes',
		'module_last_status' => 'last_status as module_last_status');
	
	/* alert related field mappings (output field => column for 'talert_template_modules', ... ) */
	
	$alert_fields_to_columns_mapping = array(
		/*** 'alert_id_agent_module (id_agent_module) is not in this list ***/
		'alert_template_modules_id'  => 't1.id as alert_template_modules_id',
		'alert_id_alert_template' => 't1.id_alert_template as alert_id_alert_template',
		'alert_internal_counter' => 't1.internal_counter as alert_internal_counter',
		'alert_last_fired' => 't1.last_fired as alert_last_fired',
		'alert_last_reference' => 't1.last_reference as alert_last_reference',
		'alert_times_fired' => 't1.times_fired as alert_times_fired',
		'alert_disabled' => 't1.disabled as alert_disabled',
		'alert_force_execution' => 't1.force_execution as alert_force_execution',
		'alert_template_modules_priority' => 't1.priority as alert_template_modules_priority',
		
		'alert_templates_id'  => 't2.id as alert_templates_id',
		'alert_type' => 't2.type as alert_type',
		'alert_value' => 't2.value as alert_value',
		'alert_matches_value' => 't2.matches_value as alert_matches_value',
		'alert_max_value' => 't2.max_value as alert_max_value',
		'alert_min_value' => 't2.min_value as alert_min_value',
		'alert_time_threshold' => 't2.time_threshold as alert_time_threshold',
		'alert_max_alerts' => 't2.max_alerts as alert_max_alerts',
		'alert_min_alerts' => 't2.min_alerts as alert_min_alerts',
		'alert_time_from' => 't2.time_from as alert_time_from',
		'alert_time_to' => 't2.time_to as alert_time_to',
		'alert_monday' => 't2.monday as alert_monday',
		'alert_tuesday' => 't2.tuesday as alert_tuesday',
		'alert_wednesday' => 't2.wednesday as alert_wednesday',
		'alert_thursday' => 't2.thursday as alert_thursday',
		'alert_friday' => 't2.friday as alert_friday',
		'alert_saturday' => 't2.saturday as alert_saturday',
		'alert_sunday' => 't2.sunday as alert_sunday',
		'alert_templates_name' => 't2.name as alert_templates_name',
		'alert_templates_description' => 't2.description as alert_templates_description',
		'alert_templates_priority' => 't2.priority as alert_templates_priority',
		'alert_templates_id_group' => 't2.id_group as alert_templates_id_group',
		'alert_recovery_notify' => 't2.recovery_notify as alert_recovery_notify',
		'alert_field2_recovery' => 't2.field2_recovery as alert_field2_recovery',
		'alert_field3_recovery' => 't2.field3_recovery as alert_field3_recovery',
		'alert_templates_field1' => 't2.field1 as alert_templates_field1',
		'alert_templates_field2' => 't2.field2 as alert_templates_field2',
		'alert_templates_field3' => 't2.field3 as alert_templates_field3',
		
		'alert_template_module_actions_id' => 't3.id as alert_template_module_actions_id',
		'alert_id_alert_action' => 't3.id_alert_action as alert_id_alert_action',
		'alert_id_alert_template_module' => 't3.id_alert_template_module as alert_id_alert_template_module',
		'alert_fires_min' => 't3.fires_min as alert_fires_min',
		'alert_fires_max' => 't3.fires_max as alert_fires_max',
		
		'alert_actions_id'  => 't4.id as alert_actions_id',
		'alert_actions_name' => 't4.name as alert_actions_name',
		'alert_id_alert_command' => 't4.id_alert_command as alert_id_alert_command',
		'alert_actions_id_group' => 't4.id_group as alert_actions_id_group',
		'alert_actions_field1' => 't4.field1 as alert_actions_field1',
		'alert_actions_field2' => 't4.field2 as alert_actions_field2',
		'alert_actions_field3' => 't4.field3 as alert_actions_field3',
		
		'alert_command'  => 't5.command as alert_command',
		'alert_internal' => 't5.internal as alert_internal',
		'alert_commands_id'  => 't5.id as alert_commands_id',
		'alert_commands_name'	=> 't5.name as alert_commands_name',
		'alert_commands_description'  => 't5.description as alert_commands_description');
	
	
	if ($fields == false) {
		$fields = $master_fields;
	}
	
	/** construct column list to query for tagente, tagente_modulo, tagente_estado and alert-related tables **/
	{
		$agent_additional_columns  = "";
		$module_additional_columns = "";
		$estado_additional_columns = "";
		$alert_additional_columns  = "";
		
		foreach ($fields as $fld ) {
			if (array_key_exists ($fld, $agent_field_column_mapping ) ) {
				$agent_additional_columns  .= (", " . $agent_field_column_mapping[$fld] );
			}
			if (array_key_exists ($fld, $module_field_column_mampping ) ) {
				$module_additional_columns .= (", " . $module_field_column_mampping[$fld]);
			}
			if (array_key_exists ($fld, $estado_fields_to_columns_mapping ) ) {
				$estado_additional_columns .= (", " . $estado_fields_to_columns_mapping[$fld]);
			}
			if (array_key_exists ($fld, $alert_fields_to_columns_mapping ) ) {
				$alert_additional_columns .= (", " . $alert_fields_to_columns_mapping[$fld]);
			}
		}
	}
	
	$returnVar = array();
	
	$groups = db_get_all_rows_sql('SELECT id_grupo as group_id, ' .
			'nombre as group_name, parent as group_parent, disabled, custom_id, ' .
			'description as group_description, contact as group_contact, ' .
			'other as group_other FROM tgrupo');
	if ($groups === false) $groups = array();
	$groups = str_replace('\n', $returnReplace, $groups);
	
	$agents = db_get_all_rows_sql('
		SELECT id_agente AS agent_id, id_grupo AS agent_id_group ' . $agent_additional_columns . ' FROM tagente');
	if ($agents === false) $agents = array();
	$agents = str_replace('\n', $returnReplace, $agents);
	
	foreach ($groups as &$group) {
		$group['type_row'] = 'group';
		$returnVar[] = $group;
		
		foreach ($agents as $index => &$agent) {
			if ($agent['agent_id_group'] == $group['group_id']) {
				
				$agent['type_row']  = 'agent';
				$returnVar[] = $agent;
				
				if ( strlen($module_additional_columns) <= 0
					&& strlen($estado_additional_columns) <= 0 
					&& strlen($alert_additional_columns) <= 0 ) {
					continue; /** SKIP collecting MODULES and ALERTS **/
				}
				
				$modules = db_get_all_rows_sql('SELECT *
					FROM (SELECT id_agente_modulo as module_id_agent_modulo ' .  $module_additional_columns . '
							FROM tagente_modulo 
							WHERE id_agente = ' . $agent['agent_id'] . ') AS t1 
						INNER JOIN (SELECT id_agente_modulo as module_id_agent_modulo ' .  $estado_additional_columns . '
							FROM tagente_estado
							WHERE id_agente = ' . $agent['agent_id'] . ') AS t2
						ON t1.module_id_agent_modulo = t2.module_id_agent_modulo');
				
				if ($modules === false) $modules = array();
				$modules = str_replace('\n', $returnReplace, $modules);
				
				foreach ($modules as &$module) {
					$module['type_row']  = 'module';

					if( $module['module_macros'] ) {
						$module['module_macros'] = base64_decode( $module['module_macros']);
					}

					$returnVar[] = $module;
					
					if ( strlen($alert_additional_columns) <= 0 ) {
						continue;	/** SKIP collecting ALERTS info **/
					}
					
					$alerts = db_get_all_rows_sql('SELECT t1.id_agent_module as alert_id_agent_module ' .  $alert_additional_columns . '
						FROM (SELECT * FROM talert_template_modules
							WHERE id_agent_module = ' . $module['module_id_agent_modulo'] . ') AS t1 
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
					
					foreach ($alerts as &$alert) {
						$alert['type_row'] = 'alert';
						$returnVar[] = $alert;
					}
				}
				unset($agents[$index]);
			}
		}
	}
	$data = array('type' => 'array', 'data' => $returnVar);
	
	$data['list_index'] = $fields;
	
	returnData($returnType, $data, $separator);
}

function api_set_update_agent($id_agent, $thrash2, $other, $thrash3) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
		//html_debug_print($other);
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
	
	$return = db_process_sql_update('tagente', 
		array('nombre' => $name,
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
			'custom_id' => $customId),
		array('id_agente' => $id_agent));
	
	returnData('string',
		array('type' => 'string', 'data' => (int)((bool)$return)));
}

/**
 * Create a new agent, and print the id for new agent.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <agent_name>;<ip>;<id_parent>;<id_group>;
 *  <cascade_protection>;<interval_sec>;<id_os>;<id_server>;<custom_id>;<learning_mode>;<disabled>;<description> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_agent&other=pepito|1.1.1.1|0|4|0|30|8|10||0|0|nose%20nose&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_set_new_agent($thrash1, $thrash2, $other, $thrash3) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$name = $other['data'][0];
	$ip = $other['data'][1];
	$idParent = $other['data'][2];
	$idGroup = $other['data'][3];
	$cascadeProtection = $other['data'][4];
	$intervalSeconds = $other['data'][5];
	$idOS = $other['data'][6];
	//$idServer = $other['data'][7];
	$nameServer = $other['data'][7];
	$customId = $other['data'][8];
	$learningMode = $other['data'][9];
	$disabled = $other['data'][10];
	$description = $other['data'][11];
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql1 = 'SELECT name
				FROM tserver WHERE name LIKE "' . $nameServer . '"';
			break;
		case "postgresql":
		case "oracle":
			$sql1 = 'SELECT name
				FROM tserver WHERE name LIKE \'' . $nameServer . '\'';
			break;
	}
	
	$nameServer = db_get_value_sql($sql1);
	
	if (agents_get_agent_id ($name)) {
		returnError('agent_name_exist', 'The name of agent yet exist in DB.');
	}
	else if (($idParent != 0) && 
		(db_get_value_sql('SELECT id_agente
			FROM tagente
			WHERE id_agente = ' . $idParent) === false)) {
		
		returnError('parent_agent_not_exist', 'The agent parent don`t exist.');
	}
	else if (db_get_value_sql('SELECT id_grupo
		FROM tgrupo
		WHERE id_grupo = ' . $idGroup) === false) {
		
		returnError('id_grupo_not_exist', 'The group don`t exist.');
	}
	else if (db_get_value_sql('SELECT id_os
		FROM tconfig_os
		WHERE id_os = ' . $idOS) === false) {
		
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
 *
 * Creates a custom field
 *
 * @param string $name Custom field name
 * @param boolean $display_front Flag to display custom field in agent's operation view
 */
function api_set_create_custom_field($t1, $t2, $other, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		
		$name = "";
		
		if ($other['data'][0] != '') {
			$name = $other['data'][0];
		}
		else {
			returnError('error_parameter', 'Custom field name required');
			return;
		}
		
		$display_front = 0;
		
		if ($other['data'][1] != '') {
			$display_front = $other['data'][1];
		}
		else {
			returnError('error_parameter', 'Custom field display flag required');
			return;
		}
		
		$result = db_process_sql_insert('tagent_custom_fields',
			array('name' => $name, 'display_on_front' => $display_front));
		
		$data['type'] = "string";
		$data["data"] = $result;
		
		returnData("string", $data);
	}
}

/**
 *
 * Returns ID of custom field zero if not exists
 *
 * @param string $name Custom field name
 */
function api_get_custom_field_id($t1, $t2, $other, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$name = $other["data"][0];
	$id = db_get_value ('id_field', 'tagent_custom_fields', 'name', $name);	
	
	$data['type'] = "string";
	$data["data"] = $id;
	returnData("string", $data);
}

/**
 * Delete a agent with the name pass as parameter.
 * 
 * @param string $id Name of agent to delete.
 * @param $thrash1 Don't use.
 * @param $thrast2 Don't use.
 * @param $thrash3 Don't use.
 */
function api_set_delete_agent($id, $thrash1, $thrast2, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agentName = $id;
	$idAgent[0] = agents_get_agent_id($agentName);
	
	if (($idAgent[0] === 0) || (!agents_delete_agent ($idAgent, true)))
		returnError('error_delete', 'Error in delete operation.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Correct Delete')));
}

/**
 * Get all agents, and print all the result like a csv or other type for example json.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <filter_so>;<filter_group>;<filter_modules_states>;<filter_name>;<filter_policy>;<csv_separator> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example for CSV:
 *  
 *  api.php?op=get&op2=all_agents&return_type=csv&other=1|2|warning|j|2|~&other_mode=url_encode_separator_|
 * 
 *  example for JSON:
 * 
 * 	api.php?op=get&op2=all_agents&return_type=json&other=1|2|warning|j|2|~&other_mode=url_encode_separator_|
 * 
 * @param $returnType.
 */
function api_get_all_agents($thrash1, $thrash2, $other, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	$where = '';

	if (isset($other['data'][0])) {
		// Filter by SO
		if ($other['data'][0] != "") {
			$where .= " AND tconfig_os.id_os = " . $other['data'][0];
		}
	}
	if (isset($other['data'][1])) {
		// Filter by group
		if ($other['data'][1] != "") {
			$where .= " AND id_grupo = " . $other['data'][1];
		}
	}
	if (isset($other['data'][3])) {
		// Filter by name
		if ($other['data'][3] != "") {
			$where .= " AND nombre LIKE ('%" . $other['data'][3] . "%')";
		}
	}
	if (isset($other['data'][4])) {
		// Filter by policy
		if ($other['data'][4] != "") {
			$filter_by_policy = enterprise_hook('policies_get_filter_by_agent', array($other['data'][4]));
			if ($filter_by_policy !== ENTERPRISE_NOT_HOOK) {
				$where .= $filter_by_policy;
			}
		}
	}
	
	if (!isset($other['data'][5]))
		$separator = ';'; //by default
	else
		$separator = $other['data'][5];
	
	// Initialization of array
	$result_agents = array();
	// Filter by state
	$sql = "SELECT id_agente, nombre, direccion, comentarios,
			tconfig_os.name, url_address
		FROM tagente, tconfig_os
		WHERE tagente.id_os = tconfig_os.id_os
			AND disabled = 0 " . $where;
	
	$all_agents = db_get_all_rows_sql($sql);
	
	// Filter by status: unknown, warning, critical, without modules 
	if (isset($other['data'][2])) {
		if ($other['data'][2] != "") {
			foreach($all_agents as $agent) {
				$filter_modules['id_agente'] = $agent['id_agente'];
				$filter_modules['disabled'] = 0;
				$filter_modules['delete_pending'] = 0;
				$modules = db_get_all_rows_filter('tagente_modulo',
					$filter_modules, 'id_agente_modulo'); 
				$result_modules = array(); 
				// Skip non init modules
				foreach ($modules as $module) {
					if (modules_get_agentmodule_is_init($module['id_agente_modulo'])){
						$result_modules[] = $module;
					}
				}
				
				// Without modules NO_MODULES
				if ($other['data'][2] == 'no_modules'){
					if (empty($result_modules) and $other['data'][2] == 'no_modules'){
						$result_agents[] = $agent;
					}
				}
				// filter by NORMAL, WARNING, CRITICAL, UNKNOWN, ALERT_FIRED
				else {
					$status = agents_get_status($agent['id_agente'], true);
					// Filter by status
					switch ($other['data'][2]) {
						case 'warning':
							if ($status == 2) {
								$result_agents[] = $agent;
							}
							break;
						case 'critical':
							if ($status == 1) {
								$result_agents[] = $agent;
							}
							break;
						case 'unknown':
							if ($status == 3) {
								$result_agents[] = $agent;
							}
							break;
						case 'normal':
							if ($status == 0) {
								$result_agents[] = $agent;
							}
							break;
						case 'alert_fired':
							if ($status == 4) {
								$result_agents[] = $agent;
							}
							break;
					}
				}
			}
		}
		else {
			$result_agents = $all_agents;
		}
	} 
	else {
		$result_agents = $all_agents;
	}
	
	if (count($result_agents) > 0 and $result_agents !== false) {
		$data = array('type' => 'array', 'data' => $result_agents);
		
		returnData($returnType, $data, $separator);
	}
	else {
		returnError('error_all_agents', 'No agents retrieved.');
	}
}

/**
 * Get modules for an agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <id_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=agents_modules&return_type=csv&other=14&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_agent_modules($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$sql = sprintf("SELECT id_agente, id_agente_modulo, nombre 
		FROM tagente_modulo
		WHERE id_agente = %d AND disabled = 0
			AND delete_pending = 0", $other['data'][0]);
	
	$all_modules = db_get_all_rows_sql($sql);
	
	if (count($all_modules) > 0 and $all_modules !== false) {
		$data = array('type' => 'array', 'data' => $all_modules);
		
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_agent_modules', 'No modules retrieved.');
	}
}

/**
 * Get modules for an agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <id_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=group_agent&return_type=csv&other=14&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_group_agent($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$sql = sprintf("SELECT groups.nombre nombre 
		FROM tagente agents, tgrupo groups
		WHERE id_agente = %d AND agents.disabled = 0
			AND groups.disabled = 0
			AND agents.id_grupo = groups.id_grupo", $other['data'][0]);
	
	$group_names = db_get_all_rows_sql($sql);
	
	if (count($group_names) > 0 and $group_names !== false) {
		$data = array('type' => 'array', 'data' => $group_names);
		
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_group_agent', 'No groups retrieved.');
	}
}

/**
 * Get all policies, possible filtered by agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <id_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=policies&return_type=csv&other=&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_policies($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$where = '';
	
	if ($other['data'][0] != "") {
		$where .= ' AND pol_agents.id_agent = ' . $other['data'][0];
		
		$sql = sprintf("SELECT policy.id, name, id_agent
			FROM tpolicies AS policy, tpolicy_agents AS pol_agents 
			WHERE policy.id = pol_agents.id_policy %s", $where);
	}
	else {
		$sql = "SELECT id, name FROM tpolicies AS policy";
	}
	
	$policies = db_get_all_rows_sql($sql);
	
	if (count($policies) > 0 and $policies !== false) {
		$data = array('type' => 'array', 'data' => $policies);
		
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_get_policies', 'No policies retrieved.');
	}
}

/**
 * Get policy modules, possible filtered by agent, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param are the filters available <id_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=get&op2=policy_modules&return_type=csv&other=2&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */
function api_get_policy_modules($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$where = '';
	
	if ($other['data'][0] == "") {
		returnError('error_policy_modules', 'Error retrieving policy modules. Id_policy cannot be left blank.');	
		return;	
	}
	
	$policies = enterprise_hook('policies_get_modules_api',
		array($other['data'][0], $other['data'][1]));
	
	if ($policies === ENTERPRISE_NOT_HOOK) {
		returnError('error_policy_modules', 'Error retrieving policy modules.');	
		return;	
	}
	
	if (count($policies) > 0 and $policies !== false){
		$data = array('type' => 'array', 'data' => $policies);
		
		returnData('csv', $data, ';');
	}
	else {
		returnError('error_policy_modules', 'No policy modules retrieved.');
	}
}


/**
 * Create a network module in agent. And return the id_agent_module of new module.
 * 
 * @param string $id Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<disabled_types_event>;<module_macros>;
 *  <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=create_network_module&id=pepito&other=prueba|0|7|1|10|15|0|16|18|0|15|0|www.google.es|0||0|180|0|0|0|0|latency%20ping&other_mode=url_encode_separator_| 
 * 
 * 
 * @param $thrash3 Don't use
 */
function api_set_create_network_module($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agentName = $id;
	
	$idAgent = agents_get_agent_id($agentName);
	
	if (!$idAgent) {
		returnError('error_create_network_module',
			__('Error in creation network module. Agent name doesn\'t exists.'));
		return;
	}
	
	if ($other['data'][2] < 6 or $other['data'][2] > 18) {
		returnError('error_create_network_module',
			__('Error in creation network module. Id_module_type is not correct for network modules.'));
		return;
	}
	
	$name = $other['data'][0];
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][22];
	$disabled_types_event = json_encode($disabled_types_event);
	
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
		'id_modulo' => 2,
		'disabled_types_event' => $disabled_types_event,
		'module_macros' => $other['data'][23],
		'each_ff' => $other['data'][24],
		'min_ff_event_normal' => $other['data'][25],
		'min_ff_event_warning' => $other['data'][26],
		'min_ff_event_critical' => $other['data'][27]
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
 * Update a network module in agent. And return a message with the result of the operation.
 * 
 * @param string $id Id of the network module to update.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_agent>;<disabled>
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<disabled_types_event>;<module_macros>;
 *  <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critidcal>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=update_network_module&id=271&other=156|0|2|10|15||16|18||7|0|127.0.0.1|0||0|300|30.00|0|0|0|latency%20ping%20modified%20by%20the%20Api&other_mode=url_encode_separator_|
 * 
 * 
 * @param $thrash3 Don't use
 */
function api_set_update_network_module($id_module, $thrash1, $other, $thrash3){
	if (defined ('METACONSOLE')) {
		return;
	}
	
	
	if ($id_module == "") {
		returnError('error_update_network_module', __('Error updating network module. Module name cannot be left blank.'));
		return;
	}
	
	$check_id_module = db_get_value ('id_agente_modulo', 'tagente_modulo', 'id_agente_modulo', $id_module);
	
	if (!$check_id_module) {
		returnError('error_update_network_module', __('Error updating network module. Id_module doesn\'t exists.'));
		return;
	}
	
	// If we want to change the module to a new agent
	if ($other['data'][0] != "") {
		$id_agent_old = db_get_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);
		
		if ($id_agent_old != $other['data'][0]) {
			$id_module_exists = db_get_value_filter ('id_agente_modulo', 'tagente_modulo', array('nombre' => $module_name, 'id_agente' => $other['data'][0]));
			
			if ($id_module_exists){
				returnError('error_update_network_module',
					__('Error updating network module. Id_module exists in the new agent.'));
				return;
			}
		}
	}
	
	$network_module_fields = array('id_agente',
		'disabled',
		'id_module_group',
		'min_warning',
		'max_warning',
		'str_warning', 
		'min_critical',
		'max_critical',
		'str_critical',
		'min_ff_event',
		'history_data',
		'ip_target',
		'tcp_port',
		'snmp_community',
		'snmp_oid',
		'module_interval',
		'post_process',
		'min',
		'max',
		'custom_id',
		'descripcion',
		'disabled_types_event',
		'module_macros',
		'each_ff',
		'min_ff_event_normal',
		'min_ff_event_warning',
		'min_ff_event_critical');
	
	$values = array();
	$cont = 0;
	foreach ($network_module_fields as $field) {
		if ($other['data'][$cont] != "") {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	$result_update = modules_update_agent_module($id_module, $values);
	
	if ($result_update < 0)
		returnError('error_update_network_module', 'Error updating network module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Network module updated.')));	
}

/**
 * Create a plugin module in agent. And return the id_agent_module of new module.
 * 
 * @param string $id Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<tcp_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *  <disabled_types_event>;<macros>;<module_macros>;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=create_plugin_module&id=pepito&other=prueba|0|1|2|0|0||0|0||0|0|127.0.0.1|0||0|300|0|0|0|0|plugin%20module%20from%20api|2|admin|pass|-p%20max&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use
 */
function api_set_create_plugin_module($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agentName = $id;
	
	if ($other['data'][22] == "") {
		returnError('error_create_plugin_module', __('Error in creation plugin module. Id_plugin cannot be left blank.'));
		return;
	}
	
	$idAgent = agents_get_agent_id($agentName);
	
	if (!$idAgent) {
		returnError('error_create_plugin_module', __('Error in creation plugin module. Agent name doesn\'t exists.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][26];
	$disabled_types_event = json_encode($disabled_types_event);
	
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
		'id_modulo' => 4,
		'id_plugin' => $other['data'][22],
		'plugin_user' => $other['data'][23],
		'plugin_pass' => $other['data'][24],
		'plugin_parameter' => $other['data'][25],
		'disabled_types_event' => $disabled_types_event,
		'macros' => base64_decode ($other['data'][27]),
		'module_macros' => $other['data'][28],
		'each_ff' => $other['data'][29],
		'min_ff_event_normal' => $other['data'][30],
		'min_ff_event_warning' => $other['data'][31],
		'min_ff_event_critical' => $other['data'][32]
	);
	
	$idModule = modules_create_agent_module($idAgent, $name, $values, true);
	
	if (is_error($idModule)) {
		// TODO: Improve the error returning more info
		returnError('error_create_plugin_module', __('Error in creation plugin module.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $idModule));
	}
}

/**
 * Update a plugin module in agent. And return the id_agent_module of new module.
 * @param string $id Id of the plugin module to update.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_agent>;<disabled>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *  <disabled_types_event>;<macros>;<module_macros>;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=update_plugin_module&id=293&other=156|0|2|0|0||0|0||0|0|127.0.0.1|0||0|300|0|0|0|0|plugin%20module%20from%20api|2|admin|pass|-p%20max&other_mode=url_encode_separator_|
 * 
 * 
 * @param $thrash3 Don't use
 */
function api_set_update_plugin_module($id_module, $thrash1, $other, $thrash3){
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id_module == "") {
		returnError('error_update_plugin_module', __('Error updating plugin module. Id_module cannot be left blank.'));
		return;
	}
	
	$check_id_module = db_get_value ('id_agente_modulo', 'tagente_modulo', 'id_agente_modulo', $id_module);
	
	if (!$check_id_module) {
		returnError('error_update_plugin_module', __('Error updating plugin module. Id_module doesn\'t exists.'));
		return;
	}
	
	// If we want to change the module to a new agent
	if ($other['data'][0] != "") {
		$id_agent_old = db_get_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);
		
		if ($id_agent_old != $other['data'][0]) {
			$id_module_exists = db_get_value_filter ('id_agente_modulo', 'tagente_modulo', array('nombre' => $module_name, 'id_agente' => $other['data'][0]));
			
			if ($id_module_exists) {
				returnError('error_update_plugin_module', __('Error updating plugin module. Id_module exists in the new agent.'));
				return;
			}
		}
	}
	
	$plugin_module_fields = array('id_agente',
		'disabled',
		'id_module_group',
		'min_warning',
		'max_warning',
		'str_warning', 
		'min_critical',
		'max_critical',
		'str_critical',
		'min_ff_event',
		'history_data',
		'ip_target',
		'tcp_port',
		'snmp_community',
		'snmp_oid',
		'module_interval',
		'post_process',
		'min',
		'max',
		'custom_id',
		'descripcion',
		'id_plugin',
		'plugin_user',
		'plugin_pass',
		'plugin_parameter',
		'disabled_types_event',
		'macros',
		'module_macros',
		'each_ff',
		'min_ff_event_normal',
		'min_ff_event_warning',
		'min_ff_event_critical');
	
	$values = array();
	$cont = 0;
	foreach ($plugin_module_fields as $field) {
		if ($other['data'][$cont] != "") {
			$values[$field] = $other['data'][$cont];

			if( $field === 'macros' ) {
				$values[$field] = base64_decode($values[$field]);
			}
		}
		
		$cont++;
	}
	
	$result_update = modules_update_agent_module($id_module, $values);
	
	if ($result_update < 0)
		returnError('error_update_plugin_module', 'Error updating plugin module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Plugin module updated.')));	
}

/**
 * Create a data module in agent. And return the id_agent_module of new module. 
 * Note: Only adds database information, this function doesn't alter config file information.
 * 
 * @param string $id Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 * 	<description>;<id_module_group>;<min_value>;<max_value>;<post_process>;<module_interval>;<min_warning>;
 * 	<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<history_data>;
 * 	<disabled_types_event>;<module_macros>;<ff_threshold>;<each_ff>;
 *	<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=create_data_module&id=pepito&other=prueba|0|1|data%20module%20from%20api|1|10|20|10.50|180|10|15||16|20||0&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use
 */
function api_set_create_data_module($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agentName = $id;
	
	if ($other['data'][0] == "") {
		returnError('error_create_data_module', __('Error in creation data module. Module_name cannot be left blank.'));
		return;
	}
	
	$idAgent = agents_get_agent_id($agentName);
	
	if (!$idAgent) {
		returnError('error_create_data_module', __('Error in creation data module. Agent name doesn\'t exists.'));
		return;
	}
	
	$name = $other['data'][0];
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][16];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array(
		'id_agente' => $idAgent,
		'disabled' => $other['data'][1],
		'id_tipo_modulo' => $other['data'][2],
		'descripcion' => $other['data'][3],
		'id_module_group' => $other['data'][4],
		'min' => $other['data'][5],
		'max' => $other['data'][6],
		'post_process' => $other['data'][7],
		'module_interval' => $other['data'][8],
		'min_warning' => $other['data'][9],
		'max_warning' => $other['data'][10],
		'str_warning' => $other['data'][11],
		'min_critical' => $other['data'][12],
		'max_critical' => $other['data'][13],
		'str_critical' => $other['data'][14],
		'history_data' => $other['data'][15],
		'id_modulo' => 1,
		'disabled_types_event' => $disabled_types_event,
		'module_macros' => $other['data'][17],
		'min_ff_event' => $other['data'][18],
		'each_ff' => $other['data'][19],
		'min_ff_event_normal' => $other['data'][20],
		'min_ff_event_warning' => $other['data'][21],
		'min_ff_event_critical' => $other['data'][22],
		'ff_timeout' => $other['data'][23]
	);
	
	$idModule = modules_create_agent_module($idAgent, $name, $values, true);
	
	if (is_error($idModule)) {
		// TODO: Improve the error returning more info
		returnError('error_create_data_module', __('Error in creation data module.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $idModule));
	}
}

/**
 * Update a data module in agent. And return a message with the result of the operation.
 * 
 * @param string $id Id of the data module to update.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_agent>;<disabled>;<description>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<disabled_types_event>;<module_macros>;<ff_threshold>;
 *  <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>;
 *  <ff_timeout> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=update_data_module&id=170&other=44|0|data%20module%20modified%20from%20API|6|0|0|50.00|300|10|15||16|18||0&other_mode=url_encode_separator_|
 * 
 * 
 * @param $thrash3 Don't use
 */
function api_set_update_data_module($id_module, $thrash1, $other, $thrash3){
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id_module == "") {
		returnError('error_update_data_module', __('Error updating data module. Id_module cannot be left blank.'));
		return;
	}
	
	$check_id_module = db_get_value ('id_agente_modulo', 'tagente_modulo', 'id_agente_modulo', $id_module);
	
	if (!$check_id_module) {
		returnError('error_update_data_module', __('Error updating data module. Id_module doesn\'t exists.'));
		return;
	}
	
	// If we want to change the module to a new agent
	if ($other['data'][0] != "") {
		$id_agent_old = db_get_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);
		
		if ($id_agent_old != $other['data'][0]) {
			$id_module_exists = db_get_value_filter ('id_agente_modulo', 'tagente_modulo', array('nombre' => $module_name, 'id_agente' => $other['data'][0]));
			
			if ($id_module_exists) {
				returnError('error_update_data_module', __('Error updating data module. Id_module exists in the new agent.'));
				return;
			}
		}
	}
	
	$data_module_fields = array('id_agente',
		'disabled',
		'descripcion',
		'id_module_group',
		'min',
		'max', 
		'post_process',
		'module_interval',
		'min_warning',
		'max_warning',
		'str_warning',
		'min_critical',
		'max_critical',
		'str_critical', 
		'history_data',
		'disabled_types_event',
		'module_macros',
		'min_ff_event',
		'each_ff',
		'min_ff_event_normal',
		'min_ff_event_warning',
		'min_ff_event_critical',
		'ff_timeout');
	
	$values = array();
	$cont = 0;
	foreach ($data_module_fields as $field) {
		if ($other['data'][$cont] != ""){
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	$result_update = modules_update_agent_module($id_module, $values);
	
	if ($result_update < 0)
		returnError('error_update_data_module', 'Error updating data module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Data module updated.')));
}


/**
 * Create a SNMP module in agent. And return the id_agent_module of new module. 
 * 
 * @param string $id Name of agent to add the module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *  <snmp3_auth_user>;<snmp3_auth_pass>;<disabled_types_event>;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 * 	example 1 (snmp v: 3, snmp3_priv_method: AES, passw|authNoPriv|MD5|pepito_user|example_priv_passw) 
 * 
 *  api.php?op=set&op2=create_snmp_module&id=pepito&other=prueba|0|15|1|10|15||16|18||15|0|127.0.0.1|60|3|public|.1.3.6.1.2.1.1.1.0|180|0|0|0|0|SNMP%20module%20from%20API|AES|example_priv_passw|authNoPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_| 
 *  
 *  example 2 (snmp v: 1)
 * 
 *  api.php?op=set&op2=create_snmp_module&id=pepito1&other=prueba2|0|15|1|10|15||16|18||15|0|127.0.0.1|60|1|public|.1.3.6.1.2.1.1.1.0|180|0|0|0|0|SNMP module from API&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use
 */
function api_set_create_snmp_module($id, $thrash1, $other, $thrash3) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agentName = $id;
	
	if ($other['data'][0] == "") {
		returnError('error_create_snmp_module', __('Error in creation SNMP module. Module_name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][2] < 15 or $other['data'][3] > 17) {
		returnError('error_create_snmp_module', __('Error in creation SNMP module. Invalid id_module_type for a SNMP module.'));
		return;
	}
	
	$idAgent = agents_get_agent_id($agentName);
	
	if (!$idAgent) {
		returnError('error_create_snmp_module', __('Error in creation SNMP module. Agent name doesn\'t exists.'));
		return;
	}
	
	$name = $other['data'][0];
	
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][27];
	$disabled_types_event = json_encode($disabled_types_event);
	
	# SNMP version 3
	if ($other['data'][14] == "3") {
		
		if ($other['data'][23] != "AES" and $other['data'][23] != "DES") {
			returnError('error_create_snmp_module', __('Error in creation SNMP module. snmp3_priv_method doesn\'t exists. Set it to \'AES\' or \'DES\'. '));
			return;
		}
		
		if ($other['data'][25] != "authNoPriv" and $other['data'][25] != "authPriv" and $other['data'][25] != "noAuthNoPriv") {
			returnError('error_create_snmp_module', __('Error in creation SNMP module. snmp3_sec_level doesn\'t exists. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '));
			return;
		}
		
		if ($other['data'][26] != "MD5" and $other['data'][26] != "SHA") {
			returnError('error_create_snmp_module', __('Error in creation SNMP module. snmp3_auth_method doesn\'t exists. Set it to \'MD5\' or \'SHA\'. '));
			return;
		}
		
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
			'tcp_send' => $other['data'][14],
			'snmp_community' => $other['data'][15],
			'snmp_oid' => $other['data'][16],
			'module_interval' => $other['data'][17],
			'post_process' => $other['data'][18],
			'min' => $other['data'][19],
			'max' => $other['data'][20],
			'custom_id' => $other['data'][21],
			'descripcion' => $other['data'][22],
			'id_modulo' => 2,
			'custom_string_1' => $other['data'][23],
			'custom_string_2' => $other['data'][24],
			'custom_string_3' => $other['data'][25],
			'plugin_parameter' => $other['data'][26],
			'plugin_user' => $other['data'][27],
			'plugin_pass' => $other['data'][28],
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][30],
			'min_ff_event_normal' => $other['data'][31],
			'min_ff_event_warning' => $other['data'][32],
			'min_ff_event_critical' => $other['data'][33]
		);
	}
	else {
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
			'tcp_send' => $other['data'][14],
			'snmp_community' => $other['data'][15],
			'snmp_oid' => $other['data'][16],
			'module_interval' => $other['data'][17],
			'post_process' => $other['data'][18],
			'min' => $other['data'][19],
			'max' => $other['data'][20],	
			'custom_id' => $other['data'][21],
			'descripcion' => $other['data'][22],
			'id_modulo' => 2,
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][24],
			'min_ff_event_normal' => $other['data'][25],
			'min_ff_event_warning' => $other['data'][26],
			'min_ff_event_critical' => $other['data'][27]
		);
	}
	
	$idModule = modules_create_agent_module($idAgent, $name, $values, true);
	
	if (is_error($idModule)) {
		// TODO: Improve the error returning more info
		returnError('error_create_snmp_module', __('Error in creation SNMP module.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $idModule));
	}
}

/**
 * Update a SNMP module in agent. And return a message with the result of the operation.
 * 
 * @param string $id Id of module to update.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_agent>;<disabled>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<ip_target>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *  <snmp3_auth_user>;<snmp3_auth_pass>;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 * 	example (update snmp v: 3, snmp3_priv_method: AES, passw|authNoPriv|MD5|pepito_user|example_priv_passw) 
 * 
 *  api.php?op=set&op2=update_snmp_module&id=example_snmp_module_name&other=44|0|6|20|25||26|30||15|1|127.0.0.1|60|3|public|.1.3.6.1.2.1.1.1.0|180|50.00|10|60|0|SNMP%20module%20modified%20by%20API|AES|example_priv_passw|authNoPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_| 
 *    
 * @param $thrash3 Don't use
 */
function api_set_update_snmp_module($id_module, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id_module == "") {
		returnError('error_update_snmp_module', __('Error updating SNMP module. Id_module cannot be left blank.'));
		return;
	}
	
	$check_id_module = db_get_value ('id_agente_modulo', 'tagente_modulo', 'id_agente_modulo', $id_module);
	
	if (!$check_id_module) {
		returnError('error_update_snmp_module', __('Error updating SNMP module. Id_module doesn\'t exists.'));
		return;
	}
	
	// If we want to change the module to a new agent
	if ($other['data'][0] != "") {
		$id_agent_old = db_get_value ('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_module);
		
		if ($id_agent_old != $other['data'][0]) {
			$id_module_exists = db_get_value_filter ('id_agente_modulo', 'tagente_modulo', array('nombre' => $module_name, 'id_agente' => $other['data'][0]));
			
			if ($id_module_exists) {
				returnError('error_update_snmp_module', __('Error updating SNMP module. Id_module exists in the new agent.'));
				return;
			}
		}
	}
	
	# SNMP version 3
	if ($other['data'][13] == "3") {
		
		if ($other['data'][22] != "AES" and $other['data'][22] != "DES") {
			returnError('error_create_snmp_module', __('Error in creation SNMP module. snmp3_priv_method doesn\'t exists. Set it to \'AES\' or \'DES\'. '));
			return;
		}
		
		if ($other['data'][24] != "authNoPriv" and $other['data'][24] != "authPriv" and $other['data'][24] != "noAuthNoPriv"){
			returnError('error_create_snmp_module', __('Error in creation SNMP module. snmp3_sec_level doesn\'t exists. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '));
			return;
		}
		
		if ($other['data'][25] != "MD5" and $other['data'][25] != "SHA") {
			returnError('error_create_snmp_module', __('Error in creation SNMP module. snmp3_auth_method doesn\'t exists. Set it to \'MD5\' or \'SHA\'. '));
			return;
		}
		
		$snmp_module_fields = array('id_agente',
			'disabled',
			'id_module_group',
			'min_warning',
			'max_warning',
			'str_warning', 
			'min_critical',
			'max_critical',
			'str_critical',
			'min_ff_event',
			'history_data',
			'ip_target',
			'tcp_port',
			'tcp_send', 
			'snmp_community',
			'snmp_oid',
			'module_interval',
			'post_process',
			'min',
			'max',
			'custom_id',
			'descripcion',
			'custom_string_1', 
			'custom_string_2',
			'custom_string_3',
			'plugin_parameter',
			'plugin_user',
			'plugin_pass',
			'disabled_types_event',
			'each_ff',
			'min_ff_event_normal',
			'min_ff_event_warning',
			'min_ff_event_critical');
	}
	else {
		$snmp_module_fields = array('id_agente',
			'disabled',
			'id_module_group',
			'min_warning',
			'max_warning',
			'str_warning', 
			'min_critical',
			'max_critical',
			'str_critical',
			'min_ff_event',
			'history_data',
			'ip_target',
			'tcp_port',
			'tcp_send', 
			'snmp_community',
			'snmp_oid',
			'module_interval',
			'post_process',
			'min',
			'max',
			'custom_id',
			'descripcion',
			'disabled_types_event',
			'each_ff',
			'min_ff_event_normal',
			'min_ff_event_warning',
			'min_ff_event_critical');
	}
	
	$values = array();
	$cont = 0;
	foreach ($snmp_module_fields as $field){
		if ($other['data'][$cont] != ""){
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	$result_update = modules_update_agent_module($id_module, $values);
	
	if ($result_update < 0)
		returnError('error_update_snmp_module', 'Error updating SNMP module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('SNMP module updated.')));	
}

/**
 * Create new network component.
 * 
 * @param $id string Name of the network component.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <network_component_type>;<description>;
 *  <module_interval>;<max_value>;<min_value>;<snmp_community>;<id_module_group>;<max_timeout>;
 *  <history_data>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;
 *  <ff_threshold>;<post_process>;<network_component_group>;<enable_unknown_events>;<each_ff>;
 *  <ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical>  in this
 *  order and separator char (after text ; ) and separator (pass in param
 *  othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_network_component&id=example_network_component_name&other=7|network%20component%20created%20by%20Api|300|30|10|public|3||1|10|20|str|21|30|str1|10|50.00|12&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.

 */
function api_set_new_network_component($id, $thrash1, $other, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_set_new_network_component', __('Error creating network component. Network component name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] < 6 or $other['data'][0] > 18) {
		returnError('error_set_new_network_component', __('Error creating network component. Incorrect value for Network component type field.'));
		return;
	}
	
	if ($other['data'][17] == "") {
		returnError('error_set_new_network_component', __('Error creating network component. Network component group cannot be left blank.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][18];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array ( 
		'description' => $other['data'][1],
		'module_interval' => $other['data'][2],
		'max' => $other['data'][3],
		'min' => $other['data'][4],
		'snmp_community' => $other['data'][5],
		'id_module_group' => $other['data'][6],
		'id_modulo' => 2,
		'max_timeout' => $other['data'][7],
		'history_data' => $other['data'][8],
		'min_warning' => $other['data'][9],
		'max_warning' => $other['data'][10],
		'str_warning' => $other['data'][11],
		'min_critical' => $other['data'][12],
		'max_critical' => $other['data'][13],
		'str_critical' => $other['data'][14],
		'min_ff_event' => $other['data'][15],
		'post_process' => $other['data'][16],
		'id_group' => $other['data'][17],
		'disabled_types_event' => $disabled_types_event,
		'each_ff' => $other['data'][19],
		'min_ff_event_normal' => $other['data'][20],
		'min_ff_event_warning' => $other['data'][21],
		'min_ff_event_critical' => $other['data'][22]);
	
	$name_check = db_get_value ('name', 'tnetwork_component', 'name', $id);
	
	if ($name_check !== false) {
		returnError('error_set_new_network_component', __('Error creating network component. This network component already exists.'));
		return;
	}
	
	$id = network_components_create_network_component ($id, $other['data'][0], $other['data'][17], $values);
	
	if (!$id)
		returnError('error_set_new_network_component', 'Error creating network component.');
	else
		returnData('string', array('type' => 'string', 'data' => $id));
}

/**
 * Create new plugin component.
 * 
 * @param $id string Name of the plugin component.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <plugin_component_type>;<description>;
 *  <module_interval>;<max_value>;<min_value>;<module_port>;<id_module_group>;<id_plugin>;<max_timeout>;
 *  <history_data>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;
 *  <ff_threshold>;<post_process>;<plugin_component_group>;<enable_unknown_events>;
 *  <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical> in this
 *  order and separator char (after text ; ) and separator (pass in param
 *  othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_plugin_component&id=example_plugin_component_name&other=2|plugin%20component%20created%20by%20Api|300|30|10|66|3|2|example_user|example_pass|-p%20max||1|10|20|str|21|30|str1|10|50.00|12&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.

 */
function api_set_new_plugin_component($id, $thrash1, $other, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	
	if ($id == "") {
		returnError('error_set_new_plugin_component',
			__('Error creating plugin component. Plugin component name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][7] == "") {
		returnError('error_set_new_plugin_component', __('Error creating plugin component. Incorrect value for Id plugin.'));
		return;
	}
	
	if ($other['data'][21] == "") {
		returnError('error_set_new_plugin_component', __('Error creating plugin component. Plugin component group cannot be left blank.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][12];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array ( 
		'description' => $other['data'][1],
		'module_interval' => $other['data'][2],
		'max' => $other['data'][3],
		'min' => $other['data'][4],
		'tcp_port' => $other['data'][5],
		'id_module_group' => $other['data'][6],
		'id_modulo' => 4,
		'id_plugin' => $other['data'][7],
		'plugin_user' => $other['data'][8],
		'plugin_pass' => $other['data'][9],
		'plugin_parameter' => $other['data'][10],
		'max_timeout' => $other['data'][11],
		'history_data' => $other['data'][12],
		'min_warning' => $other['data'][13],
		'max_warning' => $other['data'][14],
		'str_warning' => $other['data'][15],
		'min_critical' => $other['data'][16],
		'max_critical' => $other['data'][17],
		'str_critical' => $other['data'][18],
		'min_ff_event' => $other['data'][19],
		'post_process' => $other['data'][20],
		'id_group' => $other['data'][21],
		'disabled_types_event' => $disabled_types_event,
		'each_ff' => $other['data'][23],
		'min_ff_event_normal' => $other['data'][24],
		'min_ff_event_warning' => $other['data'][25],
		'min_ff_event_critical' => $other['data'][26]);
	
	$name_check = db_get_value ('name', 'tnetwork_component', 'name', $id);
	
	if ($name_check !== false) {
		returnError('error_set_new_plugin_component', __('Error creating plugin component. This plugin component already exists.'));
		return;
	}
	
	$id = network_components_create_network_component ($id, $other['data'][0], $other['data'][21], $values);
	
	if (!$id)
		returnError('error_set_new_plugin_component', 'Error creating plugin component.');
	else
		returnData('string', array('type' => 'string', 'data' => $id));
}

/**
 * Create new SNMP component.
 * 
 * @param $id string Name of the SNMP component.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <snmp_component_type>;<description>;
 *  <module_interval>;<max_value>;<min_value>;<id_module_group>;<max_timeout>;
 *  <history_data>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;
 *  <ff_threshold>;<post_process>;<snmp_version>;<snmp_oid>;<snmp_community>;
 *  <snmp3_auth_user>;<snmp3_auth_pass>;<module_port>;<snmp3_privacy_method>;<snmp3_privacy_pass>;<snmp3_auth_method>;<snmp3_security_level>;<snmp_component_group>;<enable_unknown_events>;
 *  <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical> in this
 *  order and separator char (after text ; ) and separator (pass in param
 *  othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_snmp_component&id=example_snmp_component_name&other=16|SNMP%20component%20created%20by%20Api|300|30|10|3||1|10|20|str|21|30|str1|15|50.00|3|.1.3.6.1.2.1.2.2.1.8.2|public|example_auth_user|example_auth_pass|66|AES|example_priv_pass|MD5|authNoPriv|12&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.

 */
function api_set_new_snmp_component($id, $thrash1, $other, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_set_new_snmp_component', __('Error creating SNMP component. SNMP component name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] < 15 or $other['data'][0] > 17) {
		returnError('error_set_new_snmp_component', __('Error creating SNMP component. Incorrect value for Snmp component type field.'));
		return;
	}
	
	if ($other['data'][25] == "") {
		returnError('error_set_new_snmp_component', __('Error creating SNMP component. Snmp component group cannot be left blank.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][27];
	$disabled_types_event = json_encode($disabled_types_event);
	
	# SNMP version 3
	if ($other['data'][16] == "3") {
		
		if ($other['data'][22] != "AES" and $other['data'][22] != "DES") {
			returnError('error_set_new_snmp_component', __('Error creating SNMP component. snmp3_priv_method doesn\'t exists. Set it to \'AES\' or \'DES\'. '));
			return;
		}
		
		if ($other['data'][25] != "authNoPriv" and $other['data'][25] != "authPriv" and $other['data'][25] != "noAuthNoPriv"){
			returnError('error_set_new_snmp_component', __('Error creating SNMP component. snmp3_sec_level doesn\'t exists. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '));
			return;
		}
		
		if ($other['data'][24] != "MD5" and $other['data'][24] != "SHA") {
			returnError('error_set_new_snmp_component', __('Error creating SNMP component. snmp3_auth_method doesn\'t exists. Set it to \'MD5\' or \'SHA\'. '));
			return;
		}
		
		$values = array ( 
			'description' => $other['data'][1],
			'module_interval' => $other['data'][2],
			'max' => $other['data'][3],
			'min' => $other['data'][4],
			'id_module_group' => $other['data'][5],
			'max_timeout' => $other['data'][6],
			'history_data' => $other['data'][7],
			'min_warning' => $other['data'][8],
			'max_warning' => $other['data'][9],
			'str_warning' => $other['data'][10],
			'min_critical' => $other['data'][11],
			'max_critical' => $other['data'][12],
			'str_critical' => $other['data'][13],
			'min_ff_event' => $other['data'][14],
			'post_process' => $other['data'][15],
			'tcp_send' => $other['data'][16],
			'snmp_oid' => $other['data'][17],
			'snmp_community' => $other['data'][18],
			'plugin_user' => $other['data'][19],		// snmp3_auth_user
			'plugin_pass' => $other['data'][20],		// snmp3_auth_pass
			'tcp_port' => $other['data'][21],
			'id_modulo' => 2,
			'custom_string_1' => $other['data'][22],	// snmp3_privacy_method
			'custom_string_2' => $other['data'][23],	// snmp3_privacy_pass
			'plugin_parameter' => $other['data'][24],	// snmp3_auth_method
			'custom_string_3' => $other['data'][25],	// snmp3_security_level
			'id_group' => $other['data'][26],
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][28],
			'min_ff_event_normal' => $other['data'][29],
			'min_ff_event_warning' => $other['data'][30],
			'min_ff_event_critical' => $other['data'][31]
			);
	}
	else {
		$values = array ( 
			'description' => $other['data'][1],
			'module_interval' => $other['data'][2],
			'max' => $other['data'][3],
			'min' => $other['data'][4],
			'id_module_group' => $other['data'][5],
			'max_timeout' => $other['data'][6],
			'history_data' => $other['data'][7],
			'min_warning' => $other['data'][8],
			'max_warning' => $other['data'][9],
			'str_warning' => $other['data'][10],
			'min_critical' => $other['data'][11],
			'max_critical' => $other['data'][12],
			'str_critical' => $other['data'][13],
			'min_ff_event' => $other['data'][14],
			'post_process' => $other['data'][15],
			'tcp_send' => $other['data'][16],
			'snmp_oid' => $other['data'][17],
			'snmp_community' => $other['data'][18],
			'plugin_user' => '',
			'plugin_pass' => '',
			'tcp_port' => $other['data'][21],
			'id_modulo' => 2,
			'id_group' => $other['data'][22],
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][24],
			'min_ff_event_normal' => $other['data'][25],
			'min_ff_event_warning' => $other['data'][26],
			'min_ff_event_critical' => $other['data'][27]
			);
	}
	
	$name_check = db_get_value ('name', 'tnetwork_component', 'name', $id);
	
	if ($name_check !== false) {
		returnError('error_set_new_snmp_component', __('Error creating SNMP component. This SNMP component already exists.'));
		return;
	}
	
	$id = network_components_create_network_component ($id, $other['data'][0], $other['data'][25], $values);
	
	if (!$id)
		returnError('error_set_new_snmp_component', 'Error creating SNMP component.');
	else
		returnData('string', array('type' => 'string', 'data' => $id));
}

/**
 * Create new local (data) component.
 * 
 * @param $id string Name of the local component.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <description>;<id_os>;
 *  <local_component_group>;<configuration_data>;<enable_unknown_events>;
 *  <ff_threshold>;<each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;
 *  <ff_threshold_critical>;<ff_timeout>  in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as
 *  othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=new_local_component&id=example_local_component_name&other=local%20component%20created%20by%20Api~5~12~module_begin%0dmodule_name%20example_local_component_name%0dmodule_type%20generic_data%0dmodule_exec%20ps%20|%20grep%20pid%20|%20wc%20-l%0dmodule_interval%202%0dmodule_end&other_mode=url_encode_separator_~
 *  
 * @param $thrash2 Don't use.

 */
function api_set_new_local_component($id, $thrash1, $other, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_set_new_local_component',
			__('Error creating local component. Local component name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][1] == "") {
		returnError('error_set_new_local_component',
			__('Error creating local component. Local component group cannot be left blank.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][4];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array ( 
		'description' => $other['data'][0],
		'id_network_component_group' => $other['data'][2],
		'disabled_types_event' => $disabled_types_event,
		'min_ff_event' => $other['data'][5],
		'each_ff' => $other['data'][6],
		'min_ff_event_normal' => $other['data'][7],
		'min_ff_event_warning' => $other['data'][8],
		'min_ff_event_critical' => $other['data'][9],
		'ff_timeout' => $other['data'][10]);
	
	$name_check = enterprise_hook('local_components_get_local_components',
		array(array('name' => $id), 'name'));
	
	if ($name_check === ENTERPRISE_NOT_HOOK) {
		returnError('error_set_new_local_component',
			__('Error creating local component.'));
		return;
	}
	
	if ($name_check !== false) {
		returnError('error_set_new_local_component',
			__('Error creating local component. This local component already exists.'));
		return;
	}
	
	$id = enterprise_hook('local_components_create_local_component',
		array($id, $other['data'][3], $other['data'][1], $values));
	
	if (!$id)
		returnError('error_set_new_local_component', 'Error creating local component.');
	else
		returnData('string', array('type' => 'string', 'data' => $id));
}

/**
 * Get module data value from all agents filter by module name. And return id_agents, agent_name and module value.
 * 
 * @param $id string Name of the module.
 * @param $thrash1 Don't use.
 * @param array $other Don't use.
 *  example:
 *  
 *  api.php?op=get&op2=module_value_all_agents&id=example_module_name
 *  
 * @param $thrash2 Don't use.

 */
function api_get_module_value_all_agents($id, $thrash1, $other, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_get_module_value_all_agents',
			__('Error getting module value from all agents. Module name cannot be left blank.'));
		return;
	}
	
	$id_module = db_get_value ('id_agente_modulo', 'tagente_modulo', 'nombre', $id);
	
	if ($id_module === false) {
		returnError('error_get_module_value_all_agents',
			__('Error getting module value from all agents. Module name doesn\'t exists.'));
		return;
	}
	
	$sql = sprintf("SELECT agent.id_agente, agent.nombre, module_state.datos FROM tagente agent, tagente_modulo module, tagente_estado module_state WHERE agent.id_agente = module.id_agente AND module.id_agente_modulo=module_state.id_agente_modulo AND module.nombre = '%s'", $id);
	
	$module_values = db_get_all_rows_sql($sql);
	
	if (!$module_values) {
		returnError('error_get_module_value_all_agents', 'Error getting module values from all agents.');
	}
	else {
		$data = array('type' => 'array', 'data' => $module_values);
		
		returnData('csv', $data, ';');
	}
}

/**
 * Create an alert template. And return the id of new template.
 * 
 * @param string $id Name of alert template to add.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <type>;<description>;<id_alert_action>;
 *  <field1>;<field2>;<field3>;<value>;<matches_value>;<max_value>;<min_value>;<time_threshold>;
 *  <max_alerts>;<min_alerts>;<time_from>;<time_to>;<monday>;<tuesday>;<wednesday>;
 *  <thursday>;<friday>;<saturday>;<sunday>;<recovery_notify>;<field2_recovery>;<field3_recovery>;<priority>;<id_group> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example 1 (condition: regexp =~ /pp/, action: Mail to XXX, max_alert: 10, min_alert: 0, priority: WARNING, group: databases):
 *  api.php?op=set&op2=create_alert_template&id=pepito&other=regex|template%20based%20in%20regexp|1||||pp|1||||10|0|||||||||||||3&other_mode=url_encode_separator_|
 *  
 * 	example 2 (condition: value is not between 5 and 10, max_value: 10.00, min_value: 5.00, time_from: 00:00:00, time_to: 15:00:00, priority: CRITICAL, group: Servers):
 *  api.php?op=set&op2=create_alert_template&id=template_min_max&other=max_min|template%20based%20in%20range|NULL||||||10|5||||00:00:00|15:00:00|||||||||||4|2&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_create_alert_template($name, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($name == "") {
		returnError('error_create_alert_template',
			__('Error creating alert template. Template name cannot be left blank.'));
		return;
	}
	
	$template_name = $name;
	
	$type = $other['data'][0];
	
	if ($other['data'][2] != "") {
		$values = array(
			'description' => $other['data'][1],
			'id_alert_action' => $other['data'][2],
			'field1' => $other['data'][3],
			'field2' => $other['data'][4],
			'field3' => $other['data'][5],
			'value' => $other['data'][6],
			'matches_value' => $other['data'][7],
			'max_value' => $other['data'][8],
			'min_value' => $other['data'][9],
			'time_threshold' => $other['data'][10],
			'max_alerts' => $other['data'][11],
			'min_alerts' => $other['data'][12],
			'time_from' => $other['data'][13],
			'time_to' => $other['data'][14],
			'monday' => $other['data'][15],
			'tuesday' => $other['data'][16],
			'wednesday' => $other['data'][17],
			'thursday' => $other['data'][18],
			'friday' => $other['data'][19],
			'saturday' => $other['data'][20],
			'sunday' => $other['data'][21],
			'recovery_notify' => $other['data'][22],
			'field2_recovery' => $other['data'][23],
			'field3_recovery' => $other['data'][24],
			'priority' => $other['data'][25],
			'id_group' => $other['data'][26]
		);
	}
	else {
		$values = array(
			'description' => $other['data'][1],
			'field1' => $other['data'][3],
			'field2' => $other['data'][4],
			'field3' => $other['data'][5],
			'value' => $other['data'][6],
			'matches_value' => $other['data'][7],
			'max_value' => $other['data'][8],
			'min_value' => $other['data'][9],
			'time_threshold' => $other['data'][10],
			'max_alerts' => $other['data'][11],
			'min_alerts' => $other['data'][12],
			'time_from' => $other['data'][13],
			'time_to' => $other['data'][14],
			'monday' => $other['data'][15],
			'tuesday' => $other['data'][16],
			'wednesday' => $other['data'][17],
			'thursday' => $other['data'][18],
			'friday' => $other['data'][19],
			'saturday' => $other['data'][20],
			'sunday' => $other['data'][21],
			'recovery_notify' => $other['data'][22],
			'field2_recovery' => $other['data'][23],
			'field3_recovery' => $other['data'][24],
			'priority' => $other['data'][25],
			'id_group' => $other['data'][26]
		);
	}
	
	$id_template = alerts_create_alert_template($template_name, $type, $values);
	
	if (is_error($id_template)) {
		// TODO: Improve the error returning more info
		returnError('error_create_alert_template', __('Error creating alert template.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $id_template));
	}
}

/**
 * Update an alert template. And return a message with the result of the operation.
 * 
 * @param string $id_template Id of the template to update.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <template_name>;<type>;<description>;<id_alert_action>;
 *  <field1>;<field2>;<field3>;<value>;<matches_value>;<max_value>;<min_value>;<time_threshold>;
 *  <max_alerts>;<min_alerts>;<time_from>;<time_to>;<monday>;<tuesday>;<wednesday>;
 *  <thursday>;<friday>;<saturday>;<sunday>;<recovery_notify>;<field2_recovery>;<field3_recovery>;<priority>;<id_group> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 * 
 *  example:
 * 
 * api.php?op=set&op2=update_alert_template&id=38&other=example_template_with_changed_name|onchange|changing%20from%20min_max%20to%20onchange||||||1||||5|1|||1|1|0|1|1|0|0|1|field%20recovery%20example%201|field%20recovery%20example%202|1|8&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_update_alert_template($id_template, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id_template == "") {
		returnError('error_update_alert_template',
			__('Error updating alert template. Id_template cannot be left blank.'));
		return;
	}
	
	$result_template = alerts_get_alert_template_name($id_template);
	
	if (!$result_template) {
		returnError('error_update_alert_template',
			__('Error updating alert template. Id_template doesn\'t exists.'));
		return;
	}
	
	$fields_template = array('name', 'type', 'description',
		'id_alert_action', 'field1', 'field2', 'field3', 'value',
		'matches_value', 'max_value', 'min_value', 'time_threshold',
		'max_alerts', 'min_alerts', 'time_from', 'time_to', 'monday',
		'tuesday', 'wednesday', 'thursday', 'friday', 'saturday',
		'sunday', 'recovery_notify', 'field2_recovery',
		'field3_recovery', 'priority', 'id_group');
	
	$cont = 0;
	foreach ($fields_template as $field) {
		if ($other['data'][$cont] != "") {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	$id_template = alerts_update_alert_template($id_template, $values);
	
	if (is_error($id_template)) {
		// TODO: Improve the error returning more info
		returnError('error_create_alert_template',
			__('Error updating alert template.'));
	}
	else {
		returnData('string',
			array('type' => 'string',
				'data' => __('Correct updating of alert template')));
	}
}

/**
 * Delete an alert template. And return a message with the result of the operation.
 * 
 * @param string $id_template Id of the template to delete.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=set&op2=delete_alert_template&id=38
 *    
 * @param $thrash3 Don't use
 */
function api_set_delete_alert_template($id_template, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id_template == "") {
		returnError('error_delete_alert_template',
			__('Error deleting alert template. Id_template cannot be left blank.'));
		return;
	}
	
	$result = alerts_delete_alert_template($id_template);
	
	if ($result == 0) {
		// TODO: Improve the error returning more info
		returnError('error_create_alert_template',
			__('Error deleting alert template.'));
	}
	else {
		returnData('string', array('type' => 'string',
			'data' => __('Correct deleting of alert template.')));
	}
}

/**
 * Get all alert tamplates, and print all the result like a csv.
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, but only <csv_separator> is available.
 *  example:
 *  
 *  api.php?op=get&op2=all_alert_templates&return_type=csv&other=;
 * 
 * @param $thrash3 Don't use.
 */
function api_get_all_alert_templates($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if (!isset($other['data'][0]))
		$separator = ';'; // by default
	else
		$separator = $other['data'][0];
	
	$filter_templates = false;
	
	$template = alerts_get_alert_templates();
	
	if ($template !== false) {
		$data['type'] = 'array';
		$data['data'] = $template;
	}
	
	if (!$template) {
		returnError('error_get_all_alert_templates',
			__('Error getting all alert templates.'));
	}
	else {
		returnData('csv', $data, $separator);
	}
}

/**
 * Get an alert tamplate, and print the result like a csv.
 * 
 * @param string $id_template Id of the template to get.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=get&op2=alert_template&id=25
 *    
 * @param $thrash3 Don't use
 */
function api_get_alert_template($id_template, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$filter_templates = false;
	
	if ($id_template != "") {
		$result_template = alerts_get_alert_template_name($id_template);
		
		if (!$result_template){
			returnError('error_get_alert_template',
				__('Error getting alert template. Id_template doesn\'t exists.'));
			return;
		}
		
		$filter_templates = array('id' => $id_template);
	}
	
	$template = alerts_get_alert_templates($filter_templates,
		array('id', 'name', 'description', 'id_alert_action', 'type', 'id_group'));	
	
	if ($template !== false) {
		$data['type'] = 'array';
		$data['data'] = $template;
	}
	
	if (!$template) {
		returnError('error_get_alert_template',
			__('Error getting alert template.'));
	}
	else {
		returnData('csv', $data, ';');
	}
}

/**
 * Get module groups, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, but only <csv_separator> is available.
 *  example:
 *
 *  api.php?op=get&op2=module_groups&return_type=csv&other=;
 *
 * @param $thrash3 Don't use.
 */
function api_get_module_groups($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if (!isset($other['data'][0]))
		$separator = ';'; // by default
	else
		$separator = $other['data'][0];
	
	$filter = false;
	
	$module_groups = @db_get_all_rows_filter ('tmodule_group', $filter);
	
	if ($module_groups !== false) {
		$data['type'] = 'array';
		$data['data'] = $module_groups;
	}
	
	if (!$module_groups) {
		returnError('error_get_module_groups', __('Error getting module groups.'));
	}
	else { 
		returnData('csv', $data, $separator);
	}
}

/**
 * Get plugins, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, but only <csv_separator> is available.
 *  example:
 *
 *  api.php?op=get&op2=plugins&return_type=csv&other=;
 *
 * @param $thrash3 Don't use.
 */
function api_get_plugins($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if (!isset($other['data'][0]))
		$separator = ';'; // by default
	else
		$separator = $other['data'][0];
	
	$filter = false;
	
	$plugins = @db_get_all_rows_filter ('tplugin', $filter);
	
	if ($plugins !== false) {
		$data['type'] = 'array';
		$data['data'] = $plugins;
	}
	
	if (!$plugins) {
		returnError('error_get_plugins', __('Error getting plugins.'));
	}
	else { 
		returnData('csv', $data, $separator);
	}
}

/**
 * Create a network module from a network component. And return the id of new module.
 * 
 * @param string $agent_name The name of the agent where the module will be created
 * @param string $component_name The name of the network component
 * @param $thrash1 Don't use
 * @param $thrash2 Don't use
 */
function api_set_create_network_module_from_component($agent_name, $component_name, $thrash1, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agent_id = agents_get_agent_id($agent_name);
	
	if (!$agent_id) {
		returnError('error_network_module_from_component', __('Error creating module from network component. Agent doesn\'t exists.'));
		return;
	}
	
	$component= db_get_row ('tnetwork_component', 'name', $component_name);
	
	if (!$component) {
		returnError('error_network_module_from_component', __('Error creating module from network component. Network component doesn\'t exists.'));
		return;
	}
	
	// Adapt fields to module structure
	unset($component['id_nc']);
	unset($component['id_group']);
	$component['id_tipo_modulo'] = $component['type'];
	unset($component['type']);
	$component['descripcion'] = $component['description'];
	unset($component['description']);
	unset($component['name']);
	$component['ip_target'] = agents_get_address($agent_id);
	
	// Create module
	$module_id = modules_create_agent_module ($agent_id, $component_name, $component, true);
	
	if (!$module_id) {
		returnError('error_network_module_from_component', __('Error creating module from network component. Error creating module.'));
		return;
	}
	
	return $module_id;
}

/**
 * Assign a module to an alert template. And return the id of new relationship.
 * 
 * @param string $id_template Name of alert template to add.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_module>;<id_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  api.php?op=set&op2=create_module_template&id=1&other=1|10&other_mode=url_encode_separator_|  
 *    
 * @param $thrash3 Don't use
 */
function api_set_create_module_template($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_module_to_template', __('Error assigning module to template. Id_template cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == ""){
		returnError('error_module_to_template', __('Error assigning module to template. Id_module cannot be left blank.'));
		return;
	}
	
	if ($other['data'][1] == "") {
		returnError('error_module_to_template', __('Error assigning module to template. Id_agent cannot be left blank.'));
		return;
	}
	
	$result_template = alerts_get_alert_template($id);
	
	if (!$result_template){
		returnError('error_module_to_template', __('Error assigning module to template. Id_template doensn\'t exists.'));
		return;
	}
	
	$id_module = $other['data'][0];
	$id_agent = $other['data'][1];
	
	$result_agent = agents_get_name($id_agent);
	
	if (!$result_agent) {
		returnError('error_module_to_template', __('Error assigning module to template. Id_agent doesn\'t exists.'));
		return;
	}
	
	$result_module = db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', (int) $id_module);
	
	if (!$result_module) {
		returnError('error_module_to_template', __('Error assigning module to template. Id_module doesn\'t exists.'));
		return;
	}
	
	$id_template_module = alerts_create_alert_agent_module($id_module, $id);
	
	if (is_error($id_template_module)) {
		// TODO: Improve the error returning more info
		returnError('error_module_to_template', __('Error assigning module to template.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $id_template_module));
	}
}

/**
 * Delete an module assigned to a template. And return a message with the result of the operation.
 * 
 * @param string $id Id of the relationship between module and template (talert_template_modules) to delete.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=set&op2=delete_module_template&id=38
 *    
 * @param $thrash3 Don't use
 */
function api_set_delete_module_template($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_delete_module_template', __('Error deleting module template. Id_module_template cannot be left blank.'));
		return;
	}
	
	$result_module_template = alerts_get_alert_agent_module($id);
	
	if (!$result_module_template) {
		returnError('error_delete_module_template', __('Error deleting module template. Id_module_template doesn\'t exists.'));
		return;
	}
	
	$result = alerts_delete_alert_agent_module($id);
	
	if ($result == 0) {
		// TODO: Improve the error returning more info
		returnError('error_delete_module_template', __('Error deleting module template.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Correct deleting of module template.')));
	}
}

/**
 * Delete an module assigned to a template. And return a message with the result of the operation.
 *
 * @param $id		Agent Name
 * @param $id2		Alert Template Name
 * @param $other	[0] : Module Name
 * @param $trash1	Don't use
 *
 *  example:
 *
 * api.php?op=set&op2=delete_module_template_by_names&id=my_latest_agent&id2=test_template&other=memfree
 *
 */
function api_set_delete_module_template_by_names($id, $id2, $other, $trash1) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$result = 0;
	
	if ($other['type'] != 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	
	$idAgent = agents_get_agent_id($id);
	
	$row = db_get_row_filter('talert_templates', array('name' => $id2));
	
	if ($row === false) {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	
	$idTemplate = $row['id'];
	$idActionTemplate = $row['id_alert_action'];
	
	$idAgentModule = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $idAgent, 'nombre' => $other['data']));

	if ($idAgentModule === false) {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}

	$values = array(
		'id_agent_module' => $idAgentModule,
		'id_alert_template' => $idTemplate);

	$result = db_process_sql_delete ('talert_template_modules', $values);

	if ($result == 0) {
		// TODO: Improve the error returning more info
		returnError('error_delete_module_template_by_name', __('Error deleting module template.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Correct deleting of module template.')));
	}
}

/**
 * Validate all alerts. And return a message with the result of the operation.
 * 
 * @param string Don't use.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=set&op2=validate_all_alerts
 *    
 * @param $thrash3 Don't use
 */
function api_set_validate_all_alerts($id, $thrash1, $other, $thrash3) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	// Return all groups
	$allGroups = db_get_all_rows_filter('tgrupo', array(), 'id_grupo');
	
	$groups = array();
	
	foreach ($allGroups as $row) {
		$groups[] = $row['id_grupo'];
	}
	// Added group All
	$groups[] = 0;
	
	$id_groups = implode(',', $groups);
	
	$sql = sprintf ("SELECT id_agente  
		FROM tagente 
		WHERE id_grupo IN (%s) AND disabled = 0", 
		$id_groups);
	
	$id_agents = array();
	$result_agents = array();
	
	$id_agents = db_get_all_rows_sql($sql);
	
	foreach ($id_agents as $id_agent){
		$result_agents[] = $id_agent['id_agente'];
	}
	
	$agents_string = implode(',', $result_agents);
	
	$sql = sprintf ("SELECT talert_template_modules.id
		FROM talert_template_modules
			INNER JOIN tagente_modulo t2
				ON talert_template_modules.id_agent_module = t2.id_agente_modulo
			INNER JOIN tagente t3
				ON t2.id_agente = t3.id_agente
			INNER JOIN talert_templates t4
				ON talert_template_modules.id_alert_template = t4.id
		WHERE id_agent_module in (%s)", $agents_string);	
	
	$alerts = db_get_all_rows_sql($sql);
	
	$total_alerts = count($alerts);
	$count_results = 0;
	foreach ($alerts as $alert) {
		$result = alerts_validate_alert_agent_module($alert['id'], true);
		
		if ($result) {
			$count_results++;
		}
	}
	
	if ($total_alerts > $count_results) {
		$errors = $total_alerts - $count_results;	
		returnError('error_validate_all_alerts', __('Error validate all alerts. Failed ' . $errors . '.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Correct validating of all alerts.')));	
	}
}

/**
 * Validate all policy alerts. And return a message with the result of the operation.
 * 
 * @param string Don't use.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=set&op2=validate_all_policy_alerts
 *    
 * @param $thrash3 Don't use
 */
function api_set_validate_all_policy_alerts($id, $thrash1, $other, $thrash3) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	# Get all policies
	$policies = enterprise_hook('policies_get_policies', array(false, false, false, true));
	
	if ($duplicated === ENTERPRISE_NOT_HOOK) {
		returnError('error_validate_all_policy_alerts', __('Error validating all alert policies.'));
		return;
	}
	
	// Count of valid results
	$total_alerts = 0;
	$count_results = 0;
	// Check all policies
	foreach ($policies as $policy) {
		$policy_alerts = array();
		$policy_alerts = enterprise_hook('policies_get_alerts',  array($policy['id'], false, false));
		
		
		
		// Number of alerts in this policy
		if ($policy_alerts != false){
			$partial_alerts = count($policy_alerts);
			// Added alerts of this policy to the total
			$total_alerts = $total_alerts + $partial_alerts;
		}
		
		$result_pol_alerts = array();
		foreach ($policy_alerts as $policy_alert){
			$result_pol_alerts[] = $policy_alert['id'];
		}
		
		$id_pol_alerts = implode(',', $result_pol_alerts);
		
		// If the policy has alerts
		if (count($result_pol_alerts) != 0) {
			$sql = sprintf ("SELECT id
				FROM talert_template_modules 
				WHERE id_policy_alerts IN (%s)", 
				$id_pol_alerts);
			
			$id_alerts = db_get_all_rows_sql($sql);
			
			$result_alerts = array();
			foreach ($id_alerts as $id_alert) {
				$result_alerts[] = $id_alert['id'];
			}
			
			// Validate alerts of these modules
			foreach ($result_alerts as $result_alert) {
				$result = alerts_validate_alert_agent_module($result_alert, true);
				
				if ($result) {
					$count_results++;
				}
			}
		}
		
	}
	
	// Check results
	if ($total_alerts > $count_results) {
		$errors = $total_alerts - $count_results;
		returnError('error_validate_all_alerts', __('Error validate all policy alerts. Failed ' . $errors . '.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Correct validating of all policy alerts.')));	
	}
}

/**
 * Stop a schedule downtime. And return a message with the result of the operation.
 * 
 * @param string $id Id of the downtime to stop.
 * @param $thrash1 Don't use.
 * @param array $other Don't use 
 * 
 *  example:
 * 
 * api.php?op=set&op2=stop_downtime&id=38
 *    
 * @param $thrash3 Don't use
 */
function api_set_stop_downtime($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_stop_downtime', __('Error stopping downtime. Id_downtime cannot be left blank.'));
		return;
	}
	
	$date_stop = date ("Y-m-j",get_system_time ());
	$time_stop = date ("h:iA",get_system_time ());
	$date_time_stop = strtotime ($date_stop.' '.$time_stop);
	
	$values = array();
	$values['date_to'] = $date_time_stop;
	
	$result_update = db_process_sql_update('tplanned_downtime', $values, array ('id' => $id));
	
	if ($result_update < 0)
		returnError('error_stop_downtime', 'Error stopping downtime.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Downtime stopped.')));
}

function api_set_add_tag_module($id, $id2, $thrash1, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$id_module = $id;
	$id_tag = $id2;
	
	$exists = db_get_row_filter('ttag_module',
		array('id_agente_modulo' => $id_module,
			'id_tag' => $id_tag));
	
	if (empty($exists)) {
		db_process_sql_insert('ttag_module',
			array('id_agente_modulo' => $id_module,
			'id_tag' => $id_tag));
		
		$exists = db_get_row_filter('ttag_module',
			array('id_agente_modulo' => $id_module,
				'id_tag' => $id_tag));
	}
	
	if (empty($exists))
		returnError('error_set_tag_module', 'Error set tag module.');
	else
		returnData('string',
			array('type' => 'string', 'data' => 1));
}

function api_set_remove_tag_module($id, $id2, $thrash1, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$id_module = $id;
	$id_tag = $id2;
	
	$row = db_get_row_filter('ttag_module',
		array('id_agente_modulo' => $id_module,
			'id_tag' => $id_tag));
	
	$correct = 0;
	
	if (!empty($row)) {
		
		// Avoid to delete from policies
		
		if ($row['id_policy_module'] == 0) {
			$correct = db_process_sql_delete('ttag_module',
				array('id_agente_modulo' => $id_module,
					'id_tag' => $id_tag));
		}
	}
	
	returnData('string',
		array('type' => 'string', 'data' => $correct));
}

function api_set_tag($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$values = array();
	$values['name'] = $id;
	$values['description'] = $other['data'][0];
	$values['url'] = $other['data'][1];
	$values['email'] = $other['data'][2];
	$values['phone'] = $other['data'][3];
	
	$id_tag = tags_create_tag($values);
	
	if (empty($id_tag))
		returnError('error_set_tag', 'Error set tag.');
	else
		returnData('string',
			array('type' => 'string', 'data' => $id_tag));
}

/**
 * Add agent to a policy. And return a message with the result of the operation.
 * 
 * @param string $id Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_agent> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 * api.php?op=set&op2=add_agent_policy&id=1&other=167&other_mode=url_encode_separator_|  
 *    
 * @param $thrash3 Don't use
 */
function api_set_add_agent_policy($id, $thrash1, $other, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_add_agent_policy', __('Error adding agent to policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_add_agent_policy', __('Error adding agent to policy. Id_agent cannot be left blank.'));
		return;
	}
	
	// Check if the agent exists
	$result_agent = db_get_value ('id_agente', 'tagente', 'id_agente', (int) $other['data'][0]);
	
	if (!$result_agent) {
		returnError('error_add_agent_policy', __('Error adding agent to policy. Id_agent doesn\'t exists.'));
		return;
	}
	
	// Check if the agent is already in the policy
	$id_agent_policy = enterprise_hook('policies_get_agents', array($id, array('id_agent' => $other['data'][0]), 'id'));
	
	if ($id_agent_policy === ENTERPRISE_NOT_HOOK) {
		returnError('error_add_agent_policy', __('Error adding agent to policy.'));
		return;
	}
	
	if ($id_agent_policy === false) {
		$success = enterprise_hook('policies_create_agent', array($other['data'][0], $id, true));
	}
	else {
		returnError('error_add_agent_policy', __('Error adding agent to policy. The agent is already in the policy.'));
		return;
	}
	
	if ($success)
		returnData('string', array('type' => 'string', 'data' => $success));
	else
		returnError('error_add_agent_policy', 'Error adding agent to policy.');
}

/**
 * Add data module to policy. And return id from new module.
 * 
 * @param string $id Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <module_name>;<id_module_type>;<description>;
 * <id_module_group>;<min>;<max>;<post_process>;<module_interval>;<min_warning>;<max_warning>;<str_warning>;
 * <min_critical>;<max_critical>;<str_critical>;<history_data>;<configuration_data>;
 * <disabled_types_event>;<module_macros>;<ff_threshold>;<each_ff>;<ff_threshold_normal>;
 * <ff_threshold_warning>;<ff_threshold_critical>;<ff_timeout> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=add_data_module_policy&id=1&other=data_module_policy_example_name~2~data%20module%20created%20by%20Api~2~0~0~50.00~10~20~180~~21~35~~1~module_begin%0dmodule_name%20pandora_process%0dmodule_type%20generic_data%0dmodule_exec%20ps%20aux%20|%20grep%20pandora%20|%20wc%20-l%0dmodule_end&other_mode=url_encode_separator_~
 *    
 * @param $thrash3 Don't use
 */
function api_set_add_data_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_add_data_module_policy', __('Error adding data module to policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_add_data_module_policy', __('Error adding data module to policy. Module_name cannot be left blank.'));
		return;
	}
	
	// Check if the module is already in the policy
	$name_module_policy = enterprise_hook('policies_get_modules', array($id, array('name'=>$other['data'][0]), 'name'));
	
	if ($name_module_policy === ENTERPRISE_NOT_HOOK) {
		returnError('error_add_data_module_policy', __('Error adding data module to policy.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][16];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array();
	$values['id_tipo_modulo'] = $other['data'][1];
	$values['description'] = $other['data'][2];
	$values['id_module_group'] = $other['data'][3];
	$values['min'] = $other['data'][4];
	$values['max'] = $other['data'][5];
	$values['post_process'] = $other['data'][6];
	$values['module_interval'] = $other['data'][7];
	$values['min_warning'] = $other['data'][8];
	$values['max_warning'] = $other['data'][9];
	$values['str_warning'] = $other['data'][10];
	$values['min_critical'] = $other['data'][11];
	$values['max_critical'] = $other['data'][12];
	$values['str_critical'] = $other['data'][13];
 	$values['history_data'] = $other['data'][14];
 	$values['configuration_data'] = $other['data'][15];
 	$values['disabled_types_event'] = $disabled_types_event;
	$values['module_macros'] = $other['data'][17];
	$values['min_ff_event'] = $other['data'][18];
	$values['each_ff'] = $other['data'][19];
	$values['min_ff_event_normal'] = $other['data'][20];
	$values['min_ff_event_warning'] = $other['data'][21];
	$values['min_ff_event_critical'] = $other['data'][22];
	$values['ff_timeout'] = $other['data'][23];
 	
 	if ($name_module_policy !== false) {
		if ($name_module_policy[0]['name'] == $other['data'][0]) {
			returnError('error_add_data_module_policy',
				__('Error adding data module to policy. The module is already in the policy.'));
			return;
		}
	}
	
	$success = enterprise_hook('policies_create_module',
		array($other['data'][0], $id, 1, $values, false));
	
	if ($success)
		//returnData('string', array('type' => 'string', 'data' => __('Data module added to policy. Is necessary to apply the policy in order to changes take effect.')));		
		returnData('string', array('type' => 'string', 'data' => $success));
	else
		returnError('error_add_data_module_policy', 'Error adding data module to policy.');	
	
}

/**
 * Update data module in policy. And return id from new module.
 * 
 * @param string $id Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_policy_module>;<description>;
 * <id_module_group>;<min>;<max>;<post_process>;<module_interval>;<min_warning>;<max_warning>;<str_warning>;
 * <min_critical>;<max_critical>;<str_critical>;<history_data>;<configuration_data>;
 * <disabled_types_event>;<module_macros> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=update_data_module_policy&id=1&other=10~data%20module%20updated%20by%20Api~2~0~0~50.00~10~20~180~~21~35~~1~module_begin%0dmodule_name%20pandora_process%0dmodule_type%20generic_data%0dmodule_exec%20ps%20aux%20|%20grep%20pandora%20|%20wc%20-l%0dmodule_end&other_mode=url_encode_separator_~
 *    
 * @param $thrash3 Don't use
 */
function api_set_update_data_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_update_data_module_policy', __('Error updating data module in policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_update_data_module_policy', __('Error updating data module in policy. Id_policy_module cannot be left blank.'));
		return;
	}
	
	// Check if the module exists
	$module_policy = enterprise_hook('policies_get_modules', array($id, array('id' => $other['data'][0]), 'id_module'));
	
	if ($module_policy === false) {
		returnError('error_update_data_module_policy', __('Error updating data module in policy. Module doesn\'t exists.'));
		return;
	}
	
	if ($module_policy[0]['id_module'] != 1) {
		returnError('error_update_data_module_policy', __('Error updating data module in policy. Module type is not network type.'));
		return;
	}
	
	$fields_data_module = array('id','description', 'id_module_group', 'min', 'max', 'post_process', 'module_interval', 
						 'min_warning', 'max_warning', 'str_warning', 'min_critical', 'max_critical', 'str_critical',
						 'history_data', 'configuration_data', 'disabled_types_event', 'module_macros');
	
	$cont = 0;
	foreach ($fields_data_module as $field){
		if ($other['data'][$cont] != "" and $field != 'id'){
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	 	
 
	$result_update = enterprise_hook('policies_update_module', array($other['data'][0], $values, false)); 
	
	
	if ($result_update < 0)
		returnError('error_update_data_module_policy', 'Error updating policy module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Data policy module updated.')));		
}

/**
 * Add network module to policy. And return a result message.
 * 
 * @param string $id Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <module_name>;<id_module_type>;<description>;
 * <id_module_group>;<min>;<max>;<post_process>;<module_interval>;<min_warning>;<max_warning>;<str_warning>;
 * <min_critical>;<max_critical>;<str_critical>;<history_data>;<time_threshold>;<disabled>;<module_port>;
 * <snmp_community>;<snmp_oid>;<custom_id>;<disabled_types_event>;<module_macros>;
 * <each_ff>;<ff_threshold_normal>;<ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=add_network_module_policy&id=1&other=network_module_policy_example_name|6|network%20module%20created%20by%20Api|2|0|0|50.00|180|10|20||21|35||1|15|0|66|||0&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_add_network_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_network_data_module_policy',
			__('Error adding network module to policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_network_data_module_policy',
			__('Error adding network module to policy. Module_name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][1] < 6 or $other['data'][1] > 18) {
		returnError('error_network_data_module_policy',
			__('Error adding network module to policy. Id_module_type is not correct for network modules.'));
		return;
	}
	
	// Check if the module is already in the policy
	$name_module_policy = enterprise_hook('policies_get_modules',
		array($id, array('name'=>$other['data'][0]), 'name'));
	
	if ($name_module_policy === ENTERPRISE_NOT_HOOK) {
		returnError('error_network_data_module_policy',
			__('Error adding network module to policy.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][21];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array();
	$values['id_tipo_modulo'] = $other['data'][1];
	$values['description'] = $other['data'][2];
	$values['id_module_group'] = $other['data'][3];
	$values['min'] = $other['data'][4];
	$values['max'] = $other['data'][5];
	$values['post_process'] = $other['data'][6];
	$values['module_interval'] = $other['data'][7];
	$values['min_warning'] = $other['data'][8];
	$values['max_warning'] = $other['data'][9];
	$values['str_warning'] = $other['data'][10];
	$values['min_critical'] = $other['data'][11];
	$values['max_critical'] = $other['data'][12];
	$values['str_critical'] = $other['data'][13];
	$values['history_data'] = $other['data'][14];
	$values['min_ff_event'] = $other['data'][15];
	$values['disabled'] = $other['data'][16];
	$values['tcp_port'] = $other['data'][17];
	$values['snmp_community'] = $other['data'][18];
	$values['snmp_oid'] = $other['data'][19];
	$values['custom_id'] = $other['data'][20];
	$values['disabled_types_event'] = $disabled_types_event;
	$values['module_macros'] = $other['data'][22];
	$values['each_ff'] = $other['data'][23];
	$values['min_ff_event_normal'] = $other['data'][24];
	$values['min_ff_event_warning'] = $other['data'][25];
	$values['min_ff_event_critical'] = $other['data'][26];
	
	if ($name_module_policy !== false) {
		if ($name_module_policy[0]['name'] == $other['data'][0]) {
			returnError('error_network_data_module_policy', __('Error adding network module to policy. The module is already in the policy.'));
			return;
		}
	}
	
	$success = enterprise_hook('policies_create_module', array($other['data'][0], $id, 2, $values, false)); 
	
	if ($success)
		returnData('string', array('type' => 'string', 'data' => $success));
	else
		returnError('error_add_network_module_policy', 'Error adding network module to policy.');	
}

/**
 * Update network module in policy. And return a result message.
 * 
 * @param string $id Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_policy_module>;<description>;
 * <id_module_group>;<min>;<max>;<post_process>;<module_interval>;<min_warning>;<max_warning>;<str_warning>;
 * <min_critical>;<max_critical>;<str_critical>;<history_data>;<time_threshold>;<disabled>;<module_port>;
 * <snmp_community>;<snmp_oid>;<custom_id>;<disabled_types_event>;<module_macros> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=update_network_module_policy&id=1&other=14|network%20module%20updated%20by%20Api|2|0|0|150.00|300|10|20||21|35||1|15|0|66|||0&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_update_network_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_update_network_module_policy',
			__('Error updating network module in policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_update_network_module_policy',
			__('Error updating network module in policy. Id_policy_module cannot be left blank.'));
		return;
	}
	
	// Check if the module exists
	$module_policy = enterprise_hook('policies_get_modules', array($id, array('id' => $other['data'][0]), 'id_module'));
	
	if ($module_policy === false) {
		returnError('error_update_network_module_policy',
			__('Error updating network module in policy. Module doesn\'t exists.'));
		return;
	}
	
	if ($module_policy[0]['id_module'] != 2) {
		returnError('error_update_network_module_policy',
			__('Error updating network module in policy. Module type is not network type.'));
		return;
	}
	
	$fields_network_module = array('id','description',
		'id_module_group', 'min', 'max', 'post_process',
		'module_interval', 'min_warning', 'max_warning', 'str_warning',
		'min_critical', 'max_critical', 'str_critical', 'history_data',
		'min_ff_event', 'disabled', 'tcp_port', 'snmp_community',
		'snmp_oid', 'custom_id', 'disabled_types_event', 'module_macros');
	
	$cont = 0;
	foreach ($fields_network_module as $field) {
		if ($other['data'][$cont] != "" and $field != 'id') {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	$result_update = enterprise_hook('policies_update_module', array($other['data'][0], $values, false)); 
	
	
	if ($result_update < 0)
		returnError('error_update_network_module_policy', 'Error updating policy module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Network policy module updated.')));		
}

/**
 * Add plugin module to policy. And return id from new module.
 * 
 * @param string $id Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *  <disabled_types_event>;<macros>;<module_macros>;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  api.php?op=set&op2=add_plugin_module_policy&id=1&other=example%20plugin%20module%20name|0|1|2|0|0||0|0||15|0|66|||300|50.00|0|0|0|plugin%20module%20from%20api|2|admin|pass|-p%20max&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_add_plugin_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_add_plugin_module_policy', __('Error adding plugin module to policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_add_plugin_module_policy', __('Error adding plugin module to policy. Module_name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][22] == "") {
		returnError('error_add_plugin_module_policy', __('Error adding plugin module to policy. Id_plugin cannot be left blank.'));
		return;
	}
	
	// Check if the module is already in the policy
	$name_module_policy = enterprise_hook('policies_get_modules', array($id, array('name'=>$other['data'][0]), 'name'));
	
	if ($name_module_policy === ENTERPRISE_NOT_HOOK) {
		returnError('error_add_plugin_module_policy', __('Error adding plugin module to policy.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][25];
	$disabled_types_event = json_encode($disabled_types_event);
	
	$values = array();
	$values['disabled'] = $other['data'][1];
	$values['id_tipo_modulo'] = $other['data'][2];
	$values['id_module_group'] = $other['data'][3];
	$values['min_warning'] = $other['data'][4];
	$values['max_warning'] = $other['data'][5];
	$values['str_warning'] = $other['data'][6];
	$values['min_critical'] = $other['data'][7];
	$values['max_critical'] = $other['data'][8];
	$values['str_critical'] = $other['data'][9];
	$values['min_ff_event'] = $other['data'][10];
	$values['history_data'] = $other['data'][11];
	$values['tcp_port'] = $other['data'][12];
	$values['snmp_community'] = $other['data'][13];
	$values['snmp_oid'] = $other['data'][14];
	$values['module_interval'] = $other['data'][15];
	$values['post_process'] = $other['data'][16];
	$values['min'] = $other['data'][17];
	$values['max'] = $other['data'][18];
	$values['custom_id'] = $other['data'][19];
	$values['description'] = $other['data'][20];
	$values['id_plugin'] = $other['data'][21]; 
	$values['plugin_user'] = $other['data'][22];
	$values['plugin_pass'] = $other['data'][23];
	$values['plugin_parameter'] = $other['data'][24];
	$values['disabled_types_event'] = $disabled_types_event;
	$values['macros'] = base64_decode ($other['data'][26]);
	$values['module_macros'] = $other['data'][27];
	$values['each_ff'] = $other['data'][28];
	$values['min_ff_event_normal'] = $other['data'][29];
	$values['min_ff_event_warning'] = $other['data'][30];
	$values['min_ff_event_critical'] = $other['data'][31];
	
	if ($name_module_policy !== false) {
		if ($name_module_policy[0]['name'] == $other['data'][0]) {
			returnError('error_add_plugin_module_policy', __('Error adding plugin module to policy. The module is already in the policy.'));
			return;
		}
	}
	
	$success = enterprise_hook('policies_create_module', array($other['data'][0], $id, 4, $values, false));
	
	if ($success)
		returnData('string', array('type' => 'string', 'data' => $success));
	else
		returnError('error_add_plugin_module_policy', 'Error adding plugin module to policy.');
}

/**
 * Update plugin module in policy. And return a result message.
 * 
 * @param string $id Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_policy_module>;<disabled>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<module_port>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<id_plugin>;<plugin_user>;<plugin_pass>;<plugin_parameter>;
 *  <disabled_types_event>;<macros>;<module_macros> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=update_plugin_module_policy&id=1&other=23|0|1|0|0||0|0||15|0|166|||180|150.00|0|0|0|plugin%20module%20updated%20from%20api|2|example_user|pass|-p%20min&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_update_plugin_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_update_plugin_module_policy',
			__('Error updating plugin module in policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_update_plugin_module_policy',
			__('Error updating plugin module in policy. Id_policy_module cannot be left blank.'));
		return;
	}
	
	// Check if the module exists
	$module_policy = enterprise_hook('policies_get_modules', array($id, array('id' => $other['data'][0]), 'id_module'));
	
	if ($module_policy === false) {
		returnError('error_updating_plugin_module_policy',
			__('Error updating plugin module in policy. Module doesn\'t exists.'));
		return;
	}
	
	if ($module_policy[0]['id_module'] != 4) {
		returnError('error_updating_plugin_module_policy',
			__('Error updating plugin module in policy. Module type is not network type.'));
		return;
	}
	
	$fields_plugin_module = array('id','disabled', 'id_module_group',
		'min_warning', 'max_warning', 'str_warning', 'min_critical', 
		'max_critical', 'str_critical', 'min_ff_event', 'history_data',
		'tcp_port', 'snmp_community', 'snmp_oid', 'module_interval',
		'post_process', 'min', 'max', 'custom_id', 'description',
		'id_plugin', 'plugin_user', 'plugin_pass', 'plugin_parameter',
		'disabled_types_event', 'macros', 'module_macros');
	
	$cont = 0;
	foreach ($fields_plugin_module as $field) {
		if ($other['data'][$cont] != "" and $field != 'id') {
			$values[$field] = $other['data'][$cont];
			
			if( $field === 'macros' ) {
				$values[$field] = base64_decode($values[$field]);
			}
		}
		
		$cont++;
	}
	
	$result_update = enterprise_hook('policies_update_module',
		array($other['data'][0], $values, false)); 
	
	
	if ($result_update < 0)
		returnError('error_update_plugin_module_policy', 'Error updating policy module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('Plugin policy module updated.')));		
}

/**
 * Add module data configuration into agent configuration file
 * 
 * @param string $id_agent Id of the agent
 * @param string $module_name
 * @param array $configuration_data is an array. The data in it is the new configuration data of the module
 * @param $thrash3 Don't use
 * 
 * Call example:
 * 
 *  api.php?op=set&op2=add_module_in_conf&user=admin&pass=pandora&id=9043&id2=example_name&other=bW9kdWxlX2JlZ2luCm1vZHVsZV9uYW1lIGV4YW1wbGVfbmFtZQptb2R1bGVfdHlwZSBnZW5lcmljX2RhdGEKbW9kdWxlX2V4ZWMgZWNobyAxOwptb2R1bGVfZW5k
 * 
 * @return string 0 when success, -1 when error, -2 if already exist
 */
function api_set_add_module_in_conf($id_agent, $module_name, $configuration_data, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$new_configuration_data = io_safe_output(urldecode($configuration_data['data']));
	
	// Check if exist a current module with the same name in the conf file
	$old_configuration_data = config_agents_get_module_from_conf($id_agent, io_safe_output($module_name));
	
	// If exists a module with same name, abort
	if(!empty($old_configuration_data)) {
		returnError('error_adding_module_conf', '-2');
		exit;
	}
	
	$result = enterprise_hook('config_agents_add_module_in_conf', array($id_agent, $new_configuration_data));
	
	if($result && $result !== ENTERPRISE_NOT_HOOK) {
		returnData('string', array('type' => 'string', 'data' => '0'));		
	}
	else {
		returnError('error_adding_module_conf', '-1');
	}
}


/**
 * Get module data configuration from agent configuration file
 * 
 * @param string $id_agent Id of the agent
 * @param string $module_name
 * @param $thrash2 Don't use
 * @param $thrash3 Don't use
 * 
 * Call example:
 * 
 *  api.php?op=get&op2=module_from_conf&user=admin&pass=pandora&id=9043&id2=example_name
 * 
 * @return string Module data when success, empty when error
 */
function api_get_module_from_conf($id_agent, $module_name, $thrash2, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$result = enterprise_hook('config_agents_get_module_from_conf',
		array($id_agent, io_safe_output($module_name)));
	
	if ($result !== ENTERPRISE_NOT_HOOK) {
		returnData('string', array('type' => 'string', 'data' => $result));
	}
	else {
		returnError('error_adding_module_conf', '');
	}
}

/**
 * Delete module data configuration from agent configuration file
 * 
 * @param string $id_agent Id of the agent
 * @param string $module_name
 * @param $thrash2 Don't use
 * @param $thrash3 Don't use
 * 
 * Call example:
 * 
 *  api.php?op=set&op2=delete_module_in_conf&user=admin&pass=pandora&id=9043&id2=example_name
 * 
 * @return string 0 when success, -1 when error
 */
function api_set_delete_module_in_conf($id_agent, $module_name, $thrash2, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$result = config_agents_delete_module_in_conf($id_agent, $module_name);
	
	$result = enterprise_hook('config_agents_delete_module_in_conf', array($id_agent, $module_name));
	
	if($result && $result !== ENTERPRISE_NOT_HOOK) {
		returnData('string', array('type' => 'string', 'data' => '0'));		
	}
	else {
		returnError('error_deleting_module_conf', '-1');
	}
}

/**
 * Update module data configuration from agent configuration file
 * 
 * @param string $id_agent Id of the agent
 * @param string $module_name
 * @param array $configuration_data is an array. The data in it is the new configuration data of the module
 * @param $thrash3 Don't use
 * 
 * Call example:
 * 
 *  api.php?op=set&op2=update_module_in_conf&user=admin&pass=pandora&id=9043&id2=example_name&other=bW9kdWxlX2JlZ2luCm1vZHVsZV9uYW1lIGV4YW1wbGVfbmFtZQptb2R1bGVfdHlwZSBnZW5lcmljX2RhdGEKbW9kdWxlX2V4ZWMgZWNobyAxOwptb2R1bGVfZW5k
 * 
 * @return string 0 when success, 1 when no changes, -1 when error, -2 if doesnt exist
 */
function api_set_update_module_in_conf($id_agent, $module_name, $configuration_data_serialized, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$new_configuration_data = io_safe_output(urldecode($configuration_data_serialized['data']));
	
	// Get current configuration
	$old_configuration_data = config_agents_get_module_from_conf($id_agent, io_safe_output($module_name));
	
	// If not exists
	if(empty($old_configuration_data)) {
		returnError('error_editing_module_conf', '-2');
		exit;
	}
	
	// If current configuration and new configuration are equal, abort
	if ($new_configuration_data == $old_configuration_data) {
		returnData('string', array('type' => 'string', 'data' => '1'));		
		exit;
	}
	
	$result = enterprise_hook('config_agents_update_module_in_conf', array($id_agent, $old_configuration_data, $new_configuration_data));
	
	if($result && $result !== ENTERPRISE_NOT_HOOK) {
		returnData('string', array('type' => 'string', 'data' => '0'));		
	}
	else {
		returnError('error_editing_module_conf', '-1');
	}
}

/**
 * Add SNMP module to policy. And return id from new module.
 * 
 * @param string $id Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <name_module>;<disabled>;<id_module_type>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *  <snmp3_auth_user>;<snmp3_auth_pass>;<disabled_types_event>;;<each_ff>;<ff_threshold_normal>;
 *  <ff_threshold_warning>;<ff_threshold_critical> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:  
 * 
 *  api.php?op=set&op2=add_snmp_module_policy&id=1&other=example%20SNMP%20module%20name|0|15|2|0|0||0|0||15|1|66|3|public|.1.3.6.1.2.1.1.1.0|180|50.00|10|60|0|SNMP%20module%20modified%20by%20API|AES|example_priv_passw|authNoPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_add_snmp_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_add_snmp_module_policy', __('Error adding SNMP module to policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_add_snmp_module_policy', __('Error adding SNMP module to policy. Module_name cannot be left blank.'));
		return;
	}
	
	// Check if the module is already in the policy
	$name_module_policy = enterprise_hook('policies_get_modules', array($id, array('name'=>$other['data'][0]), 'name'));
	
	if ($name_module_policy === ENTERPRISE_NOT_HOOK) {
		returnError('error_add_snmp_module_policy', __('Error adding SNMP module to policy.'));
		return;
	}
	
	if ($other['data'][2] < 15 or $other['data'][2] > 18) {
		returnError('error_add_snmp_module_policy', __('Error adding SNMP module to policy. Id_module_type is not correct for SNMP modules.'));
		return;
	}
	
	$disabled_types_event = array();
	$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][28];
	$disabled_types_event = json_encode($disabled_types_event);
	
	# SNMP version 3
	if ($other['data'][13] == "3") {
		
		if ($other['data'][22] != "AES" and $other['data'][22] != "DES") {
			returnError('error_add_snmp_module_policy', __('Error in creation SNMP module. snmp3_priv_method doesn\'t exists. Set it to \'AES\' or \'DES\'. '));
			return;
		}
		
		if ($other['data'][24] != "authNoPriv" and $other['data'][24] != "authPriv" and $other['data'][24] != "noAuthNoPriv") {
			returnError('error_add_snmp_module_policy', __('Error in creation SNMP module. snmp3_sec_level doesn\'t exists. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '));
			return;
		}
		
		if ($other['data'][25] != "MD5" and $other['data'][25] != "SHA") {
			returnError('error_add_snmp_module_policy', __('Error in creation SNMP module. snmp3_auth_method doesn\'t exists. Set it to \'MD5\' or \'SHA\'. '));
			return;
		}
		
		$values = array(
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
			'tcp_port' => $other['data'][12],
			'tcp_send' => $other['data'][13],
			'snmp_community' => $other['data'][14],
			'snmp_oid' => $other['data'][15],
			'module_interval' => $other['data'][16],
			'post_process' => $other['data'][17],
			'min' => $other['data'][18],
			'max' => $other['data'][19],
			'custom_id' => $other['data'][20],
			'description' => $other['data'][21],
			'custom_string_1' => $other['data'][22],
			'custom_string_2' => $other['data'][23],
			'custom_string_3' => $other['data'][24],
			'plugin_parameter' => $other['data'][25],
			'plugin_user' => $other['data'][26],
			'plugin_pass' => $other['data'][27],
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][29],
			'min_ff_event_normal' => $other['data'][30],
			'min_ff_event_warning' => $other['data'][31],
			'min_ff_event_critical' => $other['data'][32]
		);
	}
	else {
		$values = array(
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
			'tcp_port' => $other['data'][12],
			'tcp_send' => $other['data'][13],
			'snmp_community' => $other['data'][14],
			'snmp_oid' => $other['data'][15],
			'module_interval' => $other['data'][16],
			'post_process' => $other['data'][17],
			'min' => $other['data'][18],
			'max' => $other['data'][19],
			'custom_id' => $other['data'][20],
			'description' => $other['data'][21],
			'disabled_types_event' => $disabled_types_event,
			'each_ff' => $other['data'][23],
			'min_ff_event_normal' => $other['data'][24],
			'min_ff_event_warning' => $other['data'][25],
			'min_ff_event_critical' => $other['data'][26]
		);
	}
	
	if ($name_module_policy !== false) {
		if ($name_module_policy[0]['name'] == $other['data'][0]) {
			returnError('error_add_snmp_module_policy', __('Error adding SNMP module to policy. The module is already in the policy.'));
			return;
		}
	}
	
	$success = enterprise_hook('policies_create_module', array($other['data'][0], $id, 2, $values, false));
	
	if ($success)
		returnData('string', array('type' => 'string', 'data' => $success));
	else
		returnError('error_add_snmp_module_policy', 'Error adding SNMP module to policy.');

}

/**
 * Update SNMP module in policy. And return a result message.
 * 
 * @param string $id Id of the target policy module.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <id_policy_module>;<disabled>;
 *  <id_module_group>;<min_warning>;<max_warning>;<str_warning>;<min_critical>;<max_critical>;<str_critical>;<ff_threshold>;
 *  <history_data>;<module_port>;<snmp_version>;<snmp_community>;<snmp_oid>;<module_interval>;<post_process>;
 *  <min>;<max>;<custom_id>;<description>;<snmp3_priv_method>;<snmp3_priv_pass>;<snmp3_sec_level>;<snmp3_auth_method>;
 *  <snmp3_auth_user>;<snmp3_auth_pass> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 *  example:
 * 
 *  api.php?op=set&op2=update_snmp_module_policy&id=1&other=14|0|2|0|0||0|0||30|1|66|3|nonpublic|.1.3.6.1.2.1.1.1.0|300|150.00|10|60|0|SNMP%20module%20updated%20by%20API|DES|example_priv_passw|authPriv|MD5|pepito_user|example_auth_passw&other_mode=url_encode_separator_|
 *    
 * @param $thrash3 Don't use
 */
function api_set_update_snmp_module_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_update_snmp_module_policy', __('Error updating SNMP module in policy. Id_policy cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_update_snmp_module_policy', __('Error updating SNMP module in policy. Id_policy_module cannot be left blank.'));
		return;
	}
	
	// Check if the module exists
	$module_policy = enterprise_hook('policies_get_modules', array($id, array('id' => $other['data'][0]), 'id_module'));
	
	if ($module_policy === false) {
		returnError('error_update_snmp_module_policy', __('Error updating SNMP module in policy. Module doesn\'t exists.'));
		return;
	}
	
	if ($module_policy[0]['id_module'] != 2) {
		returnError('error_update_snmp_module_policy', __('Error updating SNMP module in policy. Module type is not SNMP type.'));
		return;
	}
	
	
	# SNMP version 3
	if ($other['data'][12] == "3") {
		
		if ($other['data'][21] != "AES" and $other['data'][21] != "DES"){
			returnError('error_update_snmp_module_policy', __('Error updating SNMP module. snmp3_priv_method doesn\'t exists. Set it to \'AES\' or \'DES\'. '));
			
			return;
		}
		
		if ($other['data'][23] != "authNoPriv" and $other['data'][23] != "authPriv" and $other['data'][23] != "noAuthNoPriv"){
			returnError('error_update_snmp_module_policy', __('Error updating SNMP module. snmp3_sec_level doesn\'t exists. Set it to \'authNoPriv\' or \'authPriv\' or \'noAuthNoPriv\'. '));
			
			return;
		}
		
		if ($other['data'][24] != "MD5" and $other['data'][24] != "SHA"){
			returnError('error_update_snmp_module_policy', __('Error updating SNMP module. snmp3_auth_method doesn\'t exists. Set it to \'MD5\' or \'SHA\'. '));
			
			return;
		}
		
		$fields_snmp_module = array('id','disabled', 'id_module_group', 'min_warning', 'max_warning', 'str_warning', 'min_critical', 
			'max_critical', 'str_critical', 'min_ff_event', 'history_data', 'tcp_port', 'tcp_send', 'snmp_community',
			'snmp_oid', 'module_interval', 'post_process', 'min', 'max', 'custom_id', 'description', 'custom_string_1',
			'custom_string_2', 'custom_string_3', 'plugin_parameter', 'plugin_user', 'plugin_pass');
	}
	else {
		$fields_snmp_module = array('id','disabled', 'id_module_group', 'min_warning', 'max_warning', 'str_warning', 'min_critical', 
			'max_critical', 'str_critical', 'min_ff_event', 'history_data', 'tcp_port', 'tcp_send', 'snmp_community',
			'snmp_oid', 'module_interval', 'post_process', 'min', 'max', 'custom_id', 'description');
	}
	
	$cont = 0;
	foreach ($fields_snmp_module as $field) {
		if ($other['data'][$cont] != "" and $field != 'id') {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	$result_update = enterprise_hook('policies_update_module', array($other['data'][0], $values, false)); 
	
	
	if ($result_update < 0)
		returnError('error_update_snmp_module_policy', 'Error updating policy module.');
	else
		returnData('string', array('type' => 'string', 'data' => __('SNMP policy module updated.')));		
}


/**
 * Apply policy. And return id from the applying operation.
 * 
 * @param string $id Id of the target policy.
 * @param $thrash1 Don't use.
 * @param array $other Don't use
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:  
 * 
 *  api.php?op=set&op2=apply_policy&id=1
 * 
 * @param $thrash3 Don't use
 */
function api_set_apply_policy($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == "") {
		returnError('error_apply_policy', __('Error applying policy. Id_policy cannot be left blank.'));
		return;
	}
	
	# Check if this operation is duplicated
	$duplicated = enterprise_hook('policies_get_policy_queue_status', array($id));
	
	if ($duplicated === ENTERPRISE_NOT_HOOK) {
		// We want to return a value
		if ($other == "return") {
			return -1;
		}
		else {
			returnError('error_apply_policy', __('Error applying policy.'));
			return;
		}
	}
	
	if ($duplicated == STATUS_IN_QUEUE_APPLYING or $duplicated == STATUS_IN_QUEUE_IN){
		// We want to return a value
		if ($other == "return") {
			return -1;
		}
		else {
			returnError('error_apply_policy', __('Error applying policy. This policy is already pending to apply.'));
			return;
		}
	}
	
	$id = enterprise_hook('add_policy_queue_operation', array($id, 0, 'apply'));
	
	if ($id === ENTERPRISE_NOT_HOOK) {
		// We want to return a value
		if ($other == "return") {
			return -1;
		}
		else {
			returnError('error_apply_policy', __('Error applying policy.'));
			return;
		}
	}
	
	// We want to return a value
	if ($other == "return") {
		if ($id)
			return $id;
		else
			return -1;
	}
	else {
		if ($id)
			returnData('string', array('type' => 'string', 'data' => $id));
		else
			returnError('error_apply_policy', 'Error applying policy.');
	}
}


/**
 * Apply all policy in database. And return the number of policies applied.
 * 
 * @param string $id Don't use.
 * @param $thrash1 Don't use.
 * @param array $other Don't use
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:  
 * 
 *  api.php?op=set&op2=apply_all_policies
 * 
 * @param $thrash3 Don't use
 */
function api_set_apply_all_policies($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$policies = array();
	
	# Get all policies
	$policies = enterprise_hook('policies_get_policies', array(false, false, false, true));
	
	if ($policies === ENTERPRISE_NOT_HOOK) {
		returnError('error_apply_all_policy', __('Error applying all policies.'));
		return;
	}
	
	$num_policies = count($policies);
	$count_results = 0;
	foreach ($policies as $policy) {
		$return_value = enterprise_hook('add_policy_queue_operation',
			array($policy['id'], 0, 'apply'));
		
		if ($return_value != -1) {
			$count_results++;
		}
	}
	
	if ($num_policies > $count_results) {
		$errors = $num_policies - $count_results;
		
		returnError('error_apply_policy', 'Error applying policy. ' . $errors . ' failed. ');	
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $count_results));		
	}
}

/**
 * Create a new group. And return the id_group of the new group. 
 * 
 * @param string $id Name of the new group.
 * @param $thrash1 Don't use.
 * @param array $other it's array, $other as param is <icon_name>;<id_group_parent>;<description> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 * 	example 1 (with parent group: Servers) 
 * 
 *  api.php?op=set&op2=create_group&id=example_group_name&other=applications|1&other_mode=url_encode_separator_|
 *  
 * 	example 2 (without parent group)
 * 
 * 	api.php?op=set&op2=create_group&id=example_group_name2&other=computer|&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use
 */
function api_set_create_group($id, $thrash1, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$group_name = $id;
	
	if ($id == "") {
		returnError('error_create_group', __('Error in group creation. Group_name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_create_group', __('Error in group creation. Icon_name cannot be left blank.'));
		return;
	}
	
	$safe_other_data = io_safe_input($other['data']);

	if ($safe_other_data[1] != "") {
	       $group = groups_get_group_by_id($safe_other_data[1]);
		
		if ($group == false) {
			returnError('error_create_group', __('Error in group creation. Id_parent_group doesn\'t exists.'));
			return;
		}
	}
	
	if ($safe_other_data[1] != "") {
		$values = array(
			'icon' => $safe_other_data[0],
			'parent' => $safe_other_data[1],
			'description' => $safe_other_data[2]
		);
	}
	else {
		$values = array(
			'icon' => $safe_other_data[0],
			'description' => $safe_other_data[2]
		);
	}
	$values['propagate'] = $safe_other_data[3];
	$values['disabled'] = $safe_other_data[4];
	$values['custom_id'] =$safe_other_data[5]; 
	$values['contact'] = $safe_other_data[6];
	$values['other'] = $safe_other_data[7];
	
	$id_group = groups_create_group($group_name, $values);
	
	if (is_error($id_group)) {
		// TODO: Improve the error returning more info
		returnError('error_create_group', __('Error in group creation.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $id_group));
	}
}

/**
 * Update a group.
 *
 * @param integer $id Group ID
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <group_name>;<icon_name>;<parent_group_id>;<propagete>;<disabled>;<custom_id>;<description>;<contact>;<other> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *
 * 	api.php?op=set&op2=update_group&id=example_group_id&other=New%20Name|application|2|new%20description|1|0|custom%20id||&other_mode=url_encode_separator_|
 *
 * @param $thrash3 Don't use
 */
function api_set_update_group($id_group, $thrash2, $other, $thrash3) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
		//html_debug_print($other);
	$name = $other['data'][0];
	$icon = $other['data'][1];
	$parent = $other['data'][2];
	$description = $other['data'][3];
	$propagate = $other['data'][4];
	$disabled = $other['data'][5];
	$custom_id = $other['data'][6];
	$contact = $other['data'][7];
	$other = $other['data'][8];

	$return = db_process_sql_update('tgrupo',
		array('nombre' => $name,
			'icon' => $icon,
			'parent' => $parent,
			'description' => $description,
			'propagate' => $propagate,
			'disabled' => $disabled,
			'custom_id' => $custom_id,
			'contact' => $contact,
			'other' => $other),
		array('id_grupo' => $id_group));

	returnData('string',
		array('type' => 'string', 'data' => (int)((bool)$return)));
}

/**
 * Create a new netflow filter. And return the id_group of the new group. 
 * 
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <filter_name>;<group_id>;<filter>;<aggregate_by>;<output_format> in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 * 
 * Possible values of 'aggregate_by' field: dstip,dstport,none,proto,srcip,srcport
 * Possible values of 'output_format' field: kilobytes,kilobytespersecond,megabytes,megabytespersecond
 * 
 *  example:
 * 
 * 	api.php?op=set&op2=create_netflow_filter&id=Filter name&other=9|host 192.168.50.3 OR host 192.168.50.4 or HOST 192.168.50.6|dstport|kilobytes&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use
 */
function api_set_create_netflow_filter($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($other['data'][0] == "") {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation. Filter name cannot be left blank.'));
		return;
	}
	
	if ($other['data'][1] == "") {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation. Group id cannot be left blank.'));
		return;
	}
	else {
		$group = groups_get_group_by_id($other['data'][1]);
		
		if ($group == false) {
			returnError('error_create_group', __('Error in group creation. Id_parent_group doesn\'t exists.'));
			return;
		}
	}
	
	if ($other['data'][2] == "") {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation. Filter cannot be left blank.'));
		return;
	}
	
	if ($other['data'][3] == "") {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation. Aggregate_by cannot be left blank.'));
		return;
	}
	
	if ($other['data'][4] == "") {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation. Output_format cannot be left blank.'));
		return;
	}
	
	$values = array (
		'id_name'=> $other['data'][0],
		'id_group' => $other['data'][1],
		'advanced_filter'=> $other['data'][2],
		'aggregate'=> $other['data'][3],
		'output'=> $other['data'][4]
	);
	
	// Save filter args
	$values['filter_args'] = netflow_get_filter_arguments ($values);
	
	$id = db_process_sql_insert('tnetflow_filter', $values);
	
	if ($id === false) {
		returnError('error_create_netflow_filter', __('Error in netflow filter creation.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $id));
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
function api_get_module_data($id, $thrash1, $other, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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
function api_get_graph_module_data($id, $thrash1, $other, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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
	
	
	$homeurl = '../';
	$ttl = 1;
	
	global $config;
	$config['flash_charts'] = 0;
	
	$image = grafico_modulo_sparse ($id, $period, $draw_events,
		$width, $height , $label, null,
		$draw_alerts, $avg_only, false,
		$date, '', 0, 0,true,
		false, $homeurl, $ttl);
	
	preg_match("/<div class=\"nodata_text\">/",
		$image, $match);
	
	if (!empty($match[0])) {
		echo "Error no data";
	}
	else {
		// Extract url of the image from img tag
		preg_match("/src='([^']*)'/i", $image, $match);
		
		if (empty($match[1])) {
			echo "Error getting graph";
		}
		else {
			header('Content-type: image/png');
			header('Location: ' . $match[1]);
		}
	}
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
function api_set_new_user($id, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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

/**
 * Update new user.
 * 
 * @param string $id String username for user login in Pandora
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <fullname>;<firstname>;<lastname>;<middlename>;<password>;
 *  <email>;<phone>;<language>;<comments>;<is_admin>;<block_size>;<flash_chart> in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 *  
 *  api.php?op=set&op2=update_user&id=example_user_name&other=example_fullname||example_lastname||example_new_passwd|example_email||example_language|example%20comment|1|30|&other_mode=url_encode_separator_|
 *  
 * @param $thrash3 Don't use.
 */
function api_set_update_user($id, $thrash2, $other, $thrash3) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$fields_user = array('fullname',
		'firstname',
		'lastname',
		'middlename',
		'password',
		'email', 
		'phone',
		'language',
		'comments',
		'is_admin',
		'block_size',
		'flash_chart');
	
	
	if ($id == "") {
		returnError('error_update_user',
			__('Error updating user. Id_user cannot be left blank.'));
		return;
	}

	$result_user = users_get_user_by_id($id);
	
	if (!$result_user) {
		returnError('error_update_user',
			__('Error updating user. Id_user doesn\'t exists.'));
		return;
	}
	
	$cont = 0;
	foreach ($fields_user as $field) {
		if ($other['data'][$cont] != "" and $field != "password") {
			$values[$field] = $other['data'][$cont];
		}
		
		$cont++;
	}
	
	// If password field has data
	if ($other['data'][4] != "") {
		if (!update_user_password($id, $other['data'][4])) {
			returnError('error_update_user', __('Error updating user. Password info incorrect.'));
			return;
		}
	}
	
	if (!update_user ($id, $values))
		returnError('error_create_user', 'Error updating user');
	else
		returnData('string', array('type' => 'string', 'data' => __('Updated user.')));
}

/**
 * Enable/disable user given an id
 * 
 * @param string $id String username for user login in Pandora
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <enable/disable value> in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 * 	example 1 (Disable user 'example_name') 
 * 
 *  api.php?op=set&op2=enable_disable_user&id=example_name&other=0&other_mode=url_encode_separator_|
 *  
 * 	example 2 (Enable user 'example_name')
 * 
 *  api.php?op=set&op2=enable_disable_user&id=example_name&other=1&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */

function api_set_enable_disable_user ($id, $thrash2, $other, $thrash3) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == ""){
		returnError('error_enable_disable_user', 'Error enable/disable user. Id_user cannot be left blank.');
		return;
	}
	
	
	if ($other['data'][0] != "0" and $other['data'][0] != "1"){
		returnError('error_enable_disable_user', 'Error enable/disable user. Enable/disable value cannot be left blank.');
		return;
	}
	
	if (users_get_user_by_id($id) == false){
		returnError('error_enable_disable_user', 'Error enable/disable user. The user doesn\'t exists.');
		return;
	}
	
	$result = users_disable($id, $other['data'][0]);
	
	if (is_error($result)) {
		// TODO: Improve the error returning more info
		returnError('error_enable_disable_user', __('Error in user enabling/disabling.'));
	}
	else {
		if ($other['data'][0] == "0"){
			returnData('string', array('type' => 'string', 'data' => __('Enabled user.')));
		}
		else {
			returnData('string', array('type' => 'string', 'data' => __('Disabled user.')));			
		}
	}
}


function otherParameter2Filter($other, $return_as_array = false) {
	$filter = array();
	
	if (isset($other['data'][1]) && ($other['data'][1] != -1) && ($other['data'][1] != '')) {
		$filter['criticity'] = $other['data'][1];
	}
	
	$idAgent = null;
	if (isset($other['data'][2]) && $other['data'][2] != '') {
		$idAgent = agents_get_agent_id($other['data'][2]);
		
		if (!empty($idAgent)) {
			$filter['id_agente'] = $idAgent;
		}
		else {
			$filter['sql'] = "1=0";
		}
	}
	
	$idAgentModulo = null;
	if (isset($other['data'][3]) && $other['data'][3] != '') {
		$filterModule = array('nombre' => $other['data'][3]);
		if ($idAgent != null) {
			$filterModule['id_agente'] = $idAgent;
		}
		$idAgentModulo = db_get_value_filter('id_agente_modulo', 'tagente_modulo', $filterModule);
		if ($idAgentModulo !== false) {
			$filter['id_agentmodule'] = $idAgentModulo;
		}
	}
	
	if (isset($other['data'][4]) && $other['data'][4] != '') {
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
	
	if (isset($other['data'][5]) && $other['data'][5] != '') {
		$filter['id_usuario'] = $other['data'][5];
	}
	
	$filterString = db_format_array_where_clause_sql ($filter);
	if ($filterString == '') {
		$filterString = '1 = 1';
	}
	
	if (isset($other['data'][6]) && ($other['data'][6] != '') && ($other['data'][6] != -1)) {
		if ($return_as_array) {
			$filter['utimestamp']['>'] = $other['data'][6];
		}
		else {
			$filterString .= ' AND utimestamp >= ' . $other['data'][6];
		}
	}
	
	if (isset($other['data'][7]) && ($other['data'][7] != '') && ($other['data'][7] != -1)) {
		if ($return_as_array) {
			$filter['utimestamp']['<'] = $other['data'][7];
		}
		else {
			$filterString .= ' AND utimestamp <= ' . $other['data'][7];
		}
	}
	
	if (isset($other['data'][8]) && ($other['data'][8] != '')) {
		if ($return_as_array) {
			$filter['estado'] = $other['data'][8];
		}
		else {
			$estado = (int)$other['data'][8];
			
			if ($estado >= 0) {
				$filterString .= ' AND estado = ' . $estado;
			}
		}
	}
	
	if (isset($other['data'][9]) && ($other['data'][9] != '')) {
		if ($return_as_array) {
			$filter['evento'] = $other['data'][9];
		}
		else {
			$filterString .= ' AND evento like "%' . $other['data'][9] . '%"';
		}
	}
	
	if (isset($other['data'][10]) && ($other['data'][10] != '')) {
		if ($return_as_array) {
			$filter['limit'] = $other['data'][10];
		}
		else {
			$filterString .= ' LIMIT ' . $other['data'][10];
		}
	}
	
	if (isset($other['data'][11]) && ($other['data'][11] != '')) {
		if ($return_as_array) {
			$filter['offset'] = $other['data'][11];
		}
		else {
			$filterString .= ' OFFSET ' . $other['data'][11];
		}
	}
	
	if (isset($other['data'][12]) && ($other['data'][12] != '')) {
		if ($return_as_array) {
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
		if ($return_as_array) {
			$filter['total'] = false;
			$filter['more_criticity'] = false;
		}
		else {
			
		}
	}
	
	if (isset($other['data'][13]) && ($other['data'][13] != '')) { 
		if ($return_as_array) {
			$filter['id_group'] = $other['data'][13];
		}
		else {
			$filterString .= ' AND id_grupo =' . $other['data'][13];
		}
	}
	
	if (isset($other['data'][14]) && ($other['data'][14] != '')) {
		
		if ($return_as_array) {
			$filter['tag'] = $other['data'][14];
		}
		else {
			$filterString .= " AND tags LIKE '" . $other['data'][14]."'";
		}
	}
	
	if (isset($other['data'][15]) && ($other['data'][15] != '')) {
		if ($return_as_array) {
			$filter['event_type'] = $other['data'][15];
		}
		else {
			$event_type = $other['data'][15];
			
			if ($event_type == "not_normal") {
				$filterString .= " AND ( event_type LIKE '%warning%'
					OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ) ";
			}
			else {
				$filterString .= ' AND event_type LIKE "%' . $event_type . '%"';
			}
		}
		
	}
	
	if ($return_as_array) {
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
function api_set_new_alert_template($id, $id2, $other, $trash1) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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

function api_set_delete_module($id, $id2, $other, $trash1) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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
			$return = modules_delete_agent_module($idAgentModule);
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

function api_set_module_data($id, $thrash2, $other, $trash1) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
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
				io_safe_output($agent['agent_version']), date('Y/m/d H:i:s', $time),
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

function api_set_new_module($id, $id2, $other, $trash1) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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
		
		$disabled_types_event = array();
		$disabled_types_event[EVENTS_GOING_UNKNOWN] = (int)!$other['data'][15];
		$disabled_types_event = json_encode($disabled_types_event);
		$values['disabled_types_event'] = $disabled_types_event;
		
		$values['id_modulo'] = 2; 
		
		$return = modules_create_agent_module($values['id_agente'],
			$values['nombre'], $values);
		
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
function api_set_alert_actions($id, $id2, $other, $trash1) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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

function api_set_new_event($trash1, $trash2, $other, $trash3) {
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
				WHERE t1.id_agent_module = 1
					AND t2.name LIKE '" . $other['data'][7] . "'");
			
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

function api_set_event_validate_filter_pro($trash1, $trash2, $other, $trash3) {
	$simulate = false;
	
	
	$table_events = 'tevento';
	if (defined ('METACONSOLE')) {
		$table_events = 'tmetaconsole_event';
	}
	
	if ($other['type'] == 'string') {
		if ($other['data'] != '') {
			returnError('error_parameter', 'Error in the parameters.');
			return;
		}
	}
	else if ($other['type'] == 'array') {
		$filter = array();
		
		if (($other['data'][1] != null) && ($other['data'][1] != -1)
			&& ($other['data'][1] != '')) {
			
			$filter['criticity'] = $other['data'][1];
		}
		
		if (($other['data'][2] != null) && ($other['data'][2] != -1)
			&& ($other['data'][2] != '')) {
			
			$filter['id_agente'] = $other['data'][2];
		}
		
		if (($other['data'][3] != null) && ($other['data'][3] != -1)
			&& ($other['data'][3] != '')) {
			
			$filter['id_agentmodule'] = $other['data'][3];
		}
		
		if (($other['data'][4] != null) && ($other['data'][4] != -1)
			&& ($other['data'][4] != '')) {
			
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
		$rows = db_get_all_rows_filter($table_events, $filterString);
		if ($rows !== false) {
			returnData('string', count($rows));
			return;
		}
	}
	else {
		$count = db_process_sql_update($table_events,
			array('estado' => 1), $filterString);
		
		returnData('string',
			array('type' => 'string', 'data' => $count));
		return;
	}
}

function api_set_event_validate_filter($trash1, $trash2, $other, $trash3) {
	$simulate = false;
	
	$table_events = 'tevento';
	if (defined ('METACONSOLE')) {
		$table_events = 'tmetaconsole_event';
	}
	
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
		$rows = db_get_all_rows_filter($table_events, $filterString);
		if ($rows !== false) {
			returnData('string', count($rows));
			return;
		}
	}
	else {
		$count = db_process_sql_update(
			$table_events, array('estado' => 1), $filterString);
		
		returnData('string',
			array('type' => 'string', 'data' => $count));
		return;
	}
}

function api_set_validate_events($id_event, $trash1, $other, $return_type, $user_in_db) {
	$text = $other['data'];
	
	// Set off the standby mode when close an event
	$event = events_get_event ($id_event);
	alerts_agent_module_standby ($event['id_alert_am'], 0);
	
	$result = events_change_status ($id_event, EVENT_VALIDATE);
	
	if ($result) {
		if (!empty($text)) {
			//Set the comment for the validation
			events_comment($id_event, $text);
		}
		
		returnData('string',
			array('type' => 'string', 'data' => 'Correct validation'));
	}
	else {
		returnError('Error in validation operation.');
	}
}

function api_get_gis_agent($id_agent, $trash1, $tresh2, $return_type, $user_in_db) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$agent_gis_data = db_get_row_sql("
		SELECT *
		FROM tgis_data_status
		WHERE tagente_id_agente = " . $id_agent);
	
	if ($agent_gis_data) {
		returnData($return_type,
			array('type' => 'array', 'data' => array($agent_gis_data)));
	}
	else {
		returnError('Error.');
	}
}

function api_set_gis_agent_only_position($id_agent, $trash1, $other, $return_type, $user_in_db) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$new_gis_data = $other['data'];
	
	$correct = true;
	
	if (isset($new_gis_data[0])) {
		$latitude = $new_gis_data[0];
	}
	else $correct = false;
	
	if (isset($new_gis_data[1])) {
		$longitude = $new_gis_data[1];
	}
	else $correct = false;
	
	if (isset($new_gis_data[2])) {
		$altitude = $new_gis_data[2];
	}
	else $correct = false;
	
	if (!$config['activate_gis']) {
		$correct = false;
	}
	else {
		if ($correct) {
			$correct = agents_update_gis($id_agent, $latitude,
				$longitude, $altitude, 0, 1, date( 'Y-m-d H:i:s'), null,
				1, __('Save by Pandora Console'),
				__('Update by Pandora Console'),
				__('Insert by Pandora Console'));
		}
	}
	
	$data = array('type' => 'string', 'data' => (int)$correct);
	
	$returnType = 'string';
	returnData($returnType, $data);
}

function api_set_gis_agent($id_agent, $trash1, $other, $return_type, $user_in_db) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$new_gis_data = $other['data'];
	
	$correct = true;
	
	if (isset($new_gis_data[0])) {
		$latitude = $new_gis_data[0];
	}
	else $correct = false;
	
	if (isset($new_gis_data[1])) {
		$longitude = $new_gis_data[1];
	}
	else $correct = false;
	
	if (isset($new_gis_data[2])) {
		$altitude = $new_gis_data[2];
	}
	else $correct = false;
	
	if (isset($new_gis_data[3])) {
		$ignore_new_gis_data = $new_gis_data[3];
	}
	else $correct = false;
	
	if (isset($new_gis_data[4])) {
		$manual_placement = $new_gis_data[4];
	}
	else $correct = false;
	
	if (isset($new_gis_data[5])) {
		$start_timestamp = $new_gis_data[5];
	}
	else $correct = false;
	
	if (isset($new_gis_data[6])) {
		$end_timestamp = $new_gis_data[6];
	}
	else $correct = false;
	
	if (isset($new_gis_data[7])) {
		$number_of_packages = $new_gis_data[7];
	}
	else $correct = false;
	
	if (isset($new_gis_data[8])) {
		$description_save_history = $new_gis_data[8];
	}
	else $correct = false;
	
	if (isset($new_gis_data[9])) {
		$description_update_gis = $new_gis_data[9];
	}
	else $correct = false;
	
	if (isset($new_gis_data[10])) {
		$description_first_insert = $new_gis_data[10];
	}
	else $correct = false;
	
	if (!$config['activate_gis']) {
		$correct = false;
	}
	else {
		if ($correct) {
			$correct = agents_update_gis($id_agent, $latitude,
				$longitude, $altitude, $ignore_new_gis_data,
				$manual_placement, $start_timestamp, $end_timestamp,
				$number_of_packages, $description_save_history,
				$description_update_gis, $description_first_insert);
		}
	}
	
	$data = array('type' => 'string', 'data' => (int)$correct);
	
	$returnType = 'string';
	returnData($returnType, $data);
}

function get_events_with_user($trash1, $trash2, $other, $returnType, $user_in_db) {
	global $config;
	
	$table_events = 'tevento';
	if (defined ('METACONSOLE')) {
		$table_events = 'tmetaconsole_event';
	}
	
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
	
	
	$id_group = (int)$filter['id_group'];
	
	$user_groups = users_get_groups ($user_in_db, "ER");
	$user_id_groups = array();
	if (!empty($user_groups))
		$user_id_groups = array_keys ($user_groups);
	
	$is_admin = (bool)db_get_value('is_admin', 'tusuario', 'id_user',
		$user_in_db);
	
	if (isset($filter['id_group'])) {
		//The admin can see all groups
		if ($is_admin) {
			if (($id_group !== -1) && ($id_group !== 0))
				$id_groups = array($id_group);
		}
		else {
			if (empty($id_group)) {
				$id_groups = $user_id_groups;
			}
			else {
				if (in_array($id_group, $user_id_groups)) {
					$id_groups = array($id_group);
				}
				else {
					$id_groups = array();
				}
			}
		}
	}
	else {
		if (!$is_admin) {
			$id_groups = $user_id_groups;
		}
	}
	
	if (isset($filter['tag']))
		$tag = $filter['tag'];
	if (isset($filter['event_type']))
		$event_type = $filter['event_type'];
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
	
	
	
	$sql_post = '';
	
	if (!empty($id_groups)) {
		$sql_post = " AND id_grupo IN (".implode (",", $id_groups).")";
	}
	else {
		//The admin can see all groups
		if (!$is_admin) {
			$sql_post = " AND 1=0";
		}
	}
	
	// Skip system messages if user is not PM
	if (!check_acl ($user_in_db, 0, "PM")) {
		$sql_post .= " AND id_grupo != 0";
	}
	
	switch ($status) {
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
			$sql_post .= " AND ( event_type LIKE '%warning%'
				OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ) ";
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
		$sql_post .= " AND id_evento = " . $id_event;
	
	if ($id_user_ack != "0")
		$sql_post .= " AND id_usuario = '" . $id_user_ack . "'";
	
	if ($utimestamp_upper != 0)
		$sql_post .= " AND utimestamp >= " . $utimestamp_upper;
	
	if ($utimestamp_bottom != 0)
		$sql_post .= " AND utimestamp <= " . $utimestamp_bottom;
	
	if ($event_view_hr > 0) {
		//Put hours in seconds
		$unixtime = get_system_time () - ($event_view_hr * SECONDS_1HOUR);
		$sql_post .= " AND (utimestamp > " . $unixtime . " OR estado = 2)";
	}
	
	//Search by tag
	if ($tag != "") {
		$sql_post .= " AND tags LIKE '" . io_safe_input($tag) . "'";
	}
	
	//Inject the raw sql
	if (isset($filter['sql'])) {
		$sql_post .= " AND (" . $filter['sql'] . ") ";
	}
	
	
	if ($group_rep == 0) {
		switch ($config["dbtype"]) {
			case "mysql":
				if ($filter['total']) {
					$sql = "SELECT COUNT(*)
						FROM " . $table_events . "
						WHERE 1=1 " . $sql_post;
				}
				else if ($filter['more_criticity']) {
					$sql = "SELECT criticity
						FROM " . $table_events . "
						WHERE 1=1 " . $sql_post . "
						ORDER BY criticity DESC
						LIMIT 1";
				}
				else {
					if (defined ('METACONSOLE')) {
						$sql = "SELECT *,
							(SELECT t2.nombre
								FROM tgrupo AS t2
								WHERE t2.id_grupo = " . $table_events . ".id_grupo) AS group_name,
							(SELECT t2.icon
								FROM tgrupo AS t2
								WHERE t2.id_grupo = " . $table_events . ".id_grupo) AS group_icon
							FROM " . $table_events . "
							WHERE 1=1 " . $sql_post . "
							ORDER BY utimestamp DESC
							LIMIT " . $offset . "," . $pagination;
					}
					else {
						$sql = "SELECT *,
							(SELECT t1.nombre
								FROM tagente AS t1
								WHERE t1.id_agente = tevento.id_agente) AS agent_name,
							(SELECT t2.nombre
								FROM tgrupo AS t2
								WHERE t2.id_grupo = tevento.id_grupo) AS group_name,
							(SELECT t2.icon
								FROM tgrupo AS t2
								WHERE t2.id_grupo = tevento.id_grupo) AS group_icon,
							(SELECT tmodule.name
								FROM tmodule
								WHERE id_module IN (
									SELECT tagente_modulo.id_modulo
									FROM tagente_modulo
									WHERE tagente_modulo.id_agente_modulo=tevento.id_agentmodule)) AS module_name
							FROM " . $table_events . "
							WHERE 1=1 " . $sql_post . "
							ORDER BY utimestamp DESC
							LIMIT " . $offset . "," . $pagination;
					}
					
				}
				break;
			case "postgresql":
				//TODO TOTAL
				$sql = "SELECT *,
					(SELECT t1.nombre
						FROM tagente AS t1
						WHERE t1.id_agente = tevento.id_agente) AS agent_name,
					(SELECT t2.nombre
						FROM tgrupo AS t2
						WHERE t2.id_grupo = tevento.id_grupo) AS group_name,
					(SELECT t2.icon
						FROM tgrupo AS t2
						WHERE t2.id_grupo = tevento.id_grupo) AS group_icon,
					(SELECT tmodule.name
						FROM tmodule
						WHERE id_module IN (
							SELECT tagente_modulo.id_modulo
							FROM tagente_modulo
							WHERE tagente_modulo.id_agente_modulo=tevento.id_agentmodule)) AS module_name
					FROM tevento
					WHERE 1=1 " . $sql_post . "
					ORDER BY utimestamp DESC
					LIMIT " . $pagination . " OFFSET " . $offset;
				break;
			case "oracle":
				//TODO TOTAL
				$set = array();
				$set['limit'] = $pagination;
				$set['offset'] = $offset;
				
				$sql = "SELECT *,
					(SELECT t1.nombre
						FROM tagente AS t1
						WHERE t1.id_agente = tevento.id_agente) AS agent_name,
					(SELECT t2.nombre
						FROM tgrupo AS t2
						WHERE t2.id_grupo = tevento.id_grupo) AS group_name,
					(SELECT t2.icon
						FROM tgrupo AS t2
						WHERE t2.id_grupo = tevento.id_grupo) AS group_icon,
					(SELECT tmodule.name
						FROM tmodule
						WHERE id_module IN (
							SELECT tagente_modulo.id_modulo
							FROM tagente_modulo
							WHERE tagente_modulo.id_agente_modulo=tevento.id_agentmodule)) AS module_name
					FROM tevento
					WHERE 1=1 " . $sql_post . " ORDER BY utimestamp DESC"; 
				$sql = oracle_recode_query ($sql, $set);
				break;
		}
	}
	else {
		switch ($config["dbtype"]) {
			case "mysql":
				db_process_sql ('SET group_concat_max_len = 9999999');
				
				$sql = "SELECT *, MAX(id_evento) AS id_evento,
						GROUP_CONCAT(DISTINCT user_comment SEPARATOR '') AS user_comment,
						MIN(estado) AS min_estado, MAX(estado) AS max_estado,
						COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep
					FROM " . $table_events . "
					WHERE 1=1 " . $sql_post . "
					GROUP BY evento, id_agentmodule
					ORDER BY timestamp_rep DESC
					LIMIT " . $offset . "," . $pagination;
				break;
			case "postgresql":
				$sql = "SELECT *, MAX(id_evento) AS id_evento,
						array_to_string(array_agg(DISTINCT user_comment), '') AS user_comment,
						MIN(estado) AS min_estado, MAX(estado) AS max_estado,
						COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep
					FROM " . $table_events . "
					WHERE 1=1 " . $sql_post . "
					GROUP BY evento, id_agentmodule
					ORDER BY timestamp_rep DESC
					LIMIT " . $pagination . " OFFSET " . $offset;
				break;
			case "oracle":
				$set = array();
				$set['limit'] = $pagination;
				$set['offset'] = $offset;
				// TODO: Remove duplicate user comments
				$sql = "SELECT a.*, b.event_rep, b.timestamp_rep
					FROM (SELECT *
						FROM tevento
						WHERE 1=1 ".$sql_post.") a, 
					(SELECT MAX (id_evento) AS id_evento,
						to_char(evento) AS evento, id_agentmodule,
						COUNT(*) AS event_rep, MIN(estado) AS min_estado,
						MAX(estado) AS max_estado,
						LISTAGG(user_comment, '') AS user_comment,
						MAX(utimestamp) AS timestamp_rep 
					FROM " . $table_events . " 
					WHERE 1=1 ".$sql_post." 
					GROUP BY to_char(evento), id_agentmodule) b 
					WHERE a.id_evento=b.id_evento AND 
						to_char(a.evento)=to_char(b.evento) AND
						a.id_agentmodule=b.id_agentmodule";
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
	//html_debug_print($sql, true);
	
	if (($result !== false) &&
		(!$filter['total']) &&
		(!$filter['more_criticity'])) {
		
		$urlImage = ui_get_full_url(false);
		
		//Add the description and image
		foreach ($result as $key => $row) {
			if (defined ('METACONSOLE')) {
				$row['agent_name'] = agents_meta_get_name (
					$row['id_agente'],
					"none", $row['server_id']);
				
				$row['module_name'] = meta_modules_get_name(
					$row['id_agentmodule'], $row['server_id']);
			}
			
			//FOR THE TEST THE API IN THE ANDROID
			//$row['evento'] = $row['id_evento'];
			
			$row['description_event'] = events_print_type_description($row["event_type"], true);
			$row['img_description'] = events_print_type_img ($row["event_type"], true, true);
			$row['criticity_name'] = get_priority_name ($row["criticity"]);
			
			switch ($row["criticity"]) {
				default:
				case EVENT_CRIT_MAINTENANCE:
					$img_sev = $urlImage .
						"/images/status_sets/default/severity_maintenance.png";
					break;
				case EVENT_CRIT_INFORMATIONAL:
					$img_sev = $urlImage .
						"/images/status_sets/default/severity_informational.png";
					break;
				case EVENT_CRIT_NORMAL:
					$img_sev = $urlImage .
						"/images/status_sets/default/severity_normal.png";
					break;
				case EVENT_CRIT_WARNING:
					$img_sev = $urlImage .
						"/images/status_sets/default/severity_warning.png";
					break;
				case EVENT_CRIT_CRITICAL:
					$img_sev = $urlImage .
						"/images/status_sets/default/severity_critical.png";
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
	
	if (empty($result))
		return false;
	
	return true;
}

/**
 * 
 * @param $trash1
 * @param $trah2
 * @param $other
 * @param $returnType
 * @param $user_in_db
 */
function api_get_events($trash1, $trash2, $other, $returnType, $user_in_db = null) {
	if ($user_in_db !== null) {
		$correct = get_events_with_user($trash1, $trash2, $other,
			$returnType, $user_in_db);
		
		$last_error = error_get_last();
		if (!$correct && !empty($last_error)) {
			$errors = array(E_ERROR, E_WARNING, E_USER_ERROR,
				E_USER_WARNING);
			if (in_array($last_error['type'], $errors)) {
				returnError('ERROR_API_PANDORAFMS', $returnType);
			}
		}
		
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
	
	
	if (defined ('METACONSOLE')) {
		$dataRows = db_get_all_rows_filter('tmetaconsole_event', $filterString);
	}
	else {
		$dataRows = db_get_all_rows_filter('tevento', $filterString);
	}
	$last_error = error_get_last();
	if (empty($dataRows)) {
		if (!empty($last_error)) {
			returnError('ERROR_API_PANDORAFMS', $returnType);
			
			return;
		}
	}
	
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
function api_set_delete_user($id, $thrash1, $thrash2, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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
 *  api.php?op=set&op2=add_user_profile&id=example_user_name&other=12|4&other_mode=url_encode_separator_|
 *  
 * @param $thrash2 Don't use.

 */
function api_set_add_user_profile($id, $thrash1, $other, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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
function api_set_delete_user_profile($id, $thrash1, $other, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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
function api_set_new_incident($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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
function api_set_new_note_incident($id, $id2, $other, $thrash2) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
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

function api_set_disable_module ($agent_name, $module_name, $thrast3, $thrash4) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	
	$result = modules_change_disabled($id_agent_module, 1);
	
	if ($result === NOERR) {
		returnData('string', array('type' => 'string', 'data' => __('Correct module disable')));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Error disabling module')));
	}
}


/**
 * Enable a module, given agent and module name.
 * 
 * @param string $agent_name Name of agent.
 * @param string $module_name Name of the module
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.
 */

function api_set_enable_module ($agent_name, $module_name, $thrast3, $thrash4) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	
	$result = modules_change_disabled($id_agent_module, 0);
	
	if ($result === NOERR) {
		returnData('string', array('type' => 'string', 'data' => __('Correct module enable')));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => __('Error enabling module')));
	}
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

function api_set_disable_alert ($agent_name, $module_name, $template_name, $thrash4) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	$id_template = db_get_value_filter('id', 'talert_templates', array('name' => $template_name["data"]));
	
	db_process_sql("UPDATE talert_template_modules
		SET disabled = 1
		WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template");
	
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

function api_set_enable_alert ($agent_name, $module_name, $template_name, $thrash4) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	$id_template = db_get_value_filter('id', 'talert_templates', array('name' => $template_name["data"]));
	
	db_process_sql("UPDATE talert_template_modules
		SET disabled = 0
		WHERE id_agent_module = $id_agent_module AND id_alert_template = $id_template");
	
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

function api_set_disable_module_alerts ($agent_name, $module_name, $thrash3, $thrash4) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	
	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	
	db_process_sql("UPDATE talert_template_modules
		SET disabled = 1
		WHERE id_agent_module = $id_agent_module");
	
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

function api_set_enable_module_alerts ($agent_name, $module_name, $thrash3, $thrash4) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$id_agent = agents_get_agent_id($agent_name);
	$id_agent_module = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('id_agente' => $id_agent, 'nombre' => $module_name));
	
	db_process_sql("UPDATE talert_template_modules
		SET disabled = 0
		WHERE id_agent_module = $id_agent_module");
	
	returnData('string', array('type' => 'string', 'data' => "Correct alerts enable"));
}

function api_get_tags($thrash1, $thrash2, $other, $returnType, $user_in_db) {
	if (defined ('METACONSOLE')) {
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
	}
	
	$tags = tags_get_all_tags();
	
	$data_tags = array();
	foreach ($tags as $id => $tag) {
		$data_tags[] = array($id, $tag);
	}
	
	$data['type'] = 'array';
	$data['data'] = $data_tags;
	
	returnData($returnType, $data, $separator);
}

/**
 * Total modules for a group given
 * 
 * @param int $id_group 
 * 
**/
// http://localhost/pandora_console/include/api.php?op=get&op2=total_modules&id=1&apipass=1234&user=admin&pass=pandora
function api_get_total_modules($id_group, $trash1, $trash2, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$sql = "SELECT COUNT(*)
		FROM tagente_modulo
		WHERE id_module_group=$id_group AND delete_pending = 0";
	
	$total = db_get_value_sql($sql);
	
	$data = array('type' => 'string', 'data' => $total);
	
	returnData($returnType, $data);
}

/**
 * Total modules for a given group
 * 
 * @param int $id_group 
 * 
**/
// http://localhost/pandora_console/include/api.php?op=get&op2=total_agents&id=2&apipass=1234&user=admin&pass=pandora
function api_get_total_agents($id_group, $trash1, $trash2, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$sql = sprintf('SELECT COUNT(*)
		FROM tagente
		WHERE id_grupo=%d AND disabled=0', $id_group);
	$total_agents = db_get_value_sql($sql);
	
	$data = array('type' => 'string', 'data' => $total_agents);
	returnData($returnType, $data);
}

/**
 * Agent name for a given id
 * 
 * @param int $id_group 
 * 
**/
// http://localhost/pandora_console/include/api.php?op=get&op2=agent_name&id=1&apipass=1234&user=admin&pass=pandora
function api_get_agent_name($id_agent, $trash1, $trash2, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$sql = sprintf('SELECT nombre
		FROM tagente
		WHERE id_agente = %d', $id_agent);
	$value = db_get_value_sql($sql);
	if ($value === false) {
		returnError('id_not_found', $returnType);
	}
	
	$data = array('type' => 'string', 'data' => $value);
	
	returnData($returnType, $data);
}

/**
 * Module name for a given id
 * 
 * @param int $id_group 
 * 
**/
// http://localhost/pandora_console/include/api.php?op=get&op2=module_name&id=20&apipass=1234&user=admin&pass=pandora
function api_get_module_name($id_module, $trash1, $trash2, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$sql = sprintf('SELECT nombre
		FROM tagente_modulo
		WHERE id_agente_modulo = %d', $id_module);
	
	$value = db_get_value_sql($sql);
	
	if ($value === false) {
		returnError('id_not_found', $returnType);
	}
	
	$data = array('type' => 'string', 'data' => $value);
	
	returnData($returnType, $data);
}

// http://localhost/pandora_console/include/api.php?op=get&op2=alert_action_by_group&id=3&id2=1&apipass=1234&user=admin&pass=pandora
function api_get_alert_action_by_group($id_group, $id_action, $trash2, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$sql = "SELECT SUM(internal_counter)
		FROM talert_template_modules
		WHERE id_alert_template IN 
			(SELECT id
			FROM talert_templates
			WHERE id_group=$id_group AND id_alert_action = $id_action)";
	
	$value = db_get_value_sql($sql);
	
	if ($value === false) {
		returnError('data_not_found', $returnType);
	}
	else if ($value == '') {
		$value = 0;
	}
	
	$data = array('type' => 'string', 'data' => $value);
	
	returnData($returnType, $data);
}

// http://localhost/pandora_console/include/api.php?op=get&op2=event_info&id=58&apipass=1234&user=admin&pass=pandora
function api_get_event_info($id_event, $trash1, $trash, $returnType) {
	
	$table_events = 'tevento';
	if (defined ('METACONSOLE')) {
		$table_events = 'tmetaconsole_event';
	}
	
	$sql = "SELECT *
		FROM " . $table_events . "
		WHERE id_evento=$id_event";
	$event_data = db_get_row_sql($sql);
	
	$i = 0;
	foreach ($event_data as $key => $data) {
		$data = strip_tags($data);
		$data = str_replace("\n",' ',$data);
		$data = str_replace(';',' ',$data);
		if ($i == 0)
			$result = $key.': '.$data.'<br>';
		else
			$result .= $key.': '.$data.'<br>'; 
		$i++;
	}
	
	$data = array('type' => 'string', 'data' => $result);
	
	returnData($returnType, $data);
	return;
}

//http://127.0.0.1/pandora_console/include/api.php?op=set&op2=create_tag&other=tag_name|tag_description|tag_url|tag_email&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
function api_set_create_tag ($id, $trash1, $other, $returnType) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$data = array();
	
	if ($other['type'] == 'string') {
		$data["name"] = $other["data"];
	}
	else if ($other['type'] == 'array') {
		
		$data['name'] = $other["data"][0];
		
		if ($other["data"][1] != '') {
			$data['description'] = $other["data"][1];
		}
		else {
			$data['description'] = "";
		}
		
		if ($other["data"][1] != '') {
			$data['url'] = $other["data"][2];
		}
		else {
			$data['url'] = "";
		}
		
		if ($other["data"][1] != '') {
			$data['email'] = $other["data"][3];
		}
		else {
			$data['email'] = '';
		}
	}
	
	tags_create_tag ($data);
}


//http://127.0.0.1/pandora_console/include/api.php?op=set&op2=create_event&id=name_event&other=2|system|3|admin|2|1|10|0|comments||Pandora||critical_inst|warning_inst|unknown_inst|other||&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
function api_set_create_event($id, $trash1, $other, $returnType) {
	
	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		
		$values = array();
		
		if ($other['data'][0] != '') {
			$values['event'] = $other['data'][0];
		}
		else {
			returnError('error_parameter', 'Event text required.');
			return;
		}
		
		if ($other['data'][1] != '') {
			$values['id_grupo'] = $other['data'][1];
		}
		else {
			returnError('error_parameter', 'Group ID required.');
			return;
		}
		
		if ($other['data'][2] != '') {
			$values['id_agente'] = $other['data'][2];
		}
		else {
			returnError('error_parameter', 'Agent ID required.');
			return;
		}
		
		if ($other['data'][3] != '') {
			$values['status'] = $other['data'][3];
		}
		else {
			$values['status'] = 0;
		}
		
		$values['id_usuario'] = $other['data'][4];
		
		if ($other['data'][5] != '') {
			$values['event_type'] = $other['data'][5];
		}
		else {
			$values['event_type'] = "unknown";
		}
		
		if ($other['data'][6] != '') {
			$values['priority'] = $other['data'][6];
		}
		else {
			$values['priority'] = 0;
		}
		
		if ($other['data'][7] != '') {
			$values['id_agentmodule'] = $other['data'][7];
		}
		else {
			$value['id_agentmodule'] = 0;
		}
		
		if ($other['data'][8] != '') {
			$values['id_alert_am'] = $other['data'][8];
		}
		else {
			$values['id_alert_am'] = 0;
		}
		
		if ($other['data'][9] != '') {
			$values['critical_instructions'] = $other['data'][9];
		}
		else {
			$values['critical_instructions'] = '';
		}
		
		if ($other['data'][10] != '') {
			$values['warning_instructions'] = $other['data'][10];
		}
		else {
			$values['warning_instructions'] = '';
		}
		
		if ($other['data'][11] != '') {
			$values['unknown_instructions'] = $other['data'][11];
		}
		else {
			$values['unknown_instructions'] = '';
		}
		
		if ($other['data'][14] != '') {
			$values['source'] = $other['data'][14];
		}
		else {
			$values['source'] = "Pandora";
		}
		
		if ($other['data'][15] != '') {
			$values['tags'] = $other['data'][15];
		}
		else {
			$values['tags'] = "";
		}
		
		if ($other['data'][16] != '') {
			$values['custom_data'] = $other['data'][16];
		}
		else {
			$values['custom_data'] = "";
		}
		
		if ($other['data'][17] != '') {
			$values['server_id'] = $other['data'][17];
		}
		else {
			$values['server_id'] = 0;
		}
		
		$return = events_create_event(
			$values['event'], $values['id_grupo'], $values['id_agente'], 
			$values['status'], $values['id_usuario'],
			$values['event_type'], $values['priority'],
			$values['id_agentmodule'], $values['id_alert_am'], 
			$values['critical_instructions'],
			$values['warning_instructions'], 
			$values['unknown_instructions'], $values['source'],
			$values['tags'], $values['custom_data'],
			$values['server_id']);
		
		if ($other['data'][12] != '') { //user comments
			if ($return !== false) { //event successfully created
				$user_comment = $other['data'][12];
				$res = events_comment ($return, $user_comment,
					'Added comment', defined ('METACONSOLE'),
					$config['history_db_enabled']);
				if ($other['data'][13] != '') { //owner user
					if ($res !== false) { //comment added
						$owner_user = $other['data'][13];
						events_change_owner ($return, $owner_user,
							true, defined ('METACONSOLE'),
							$config['history_db_enabled']);
					}
				}
			}
		}
		
		$data['type'] = 'string';
		if ($return === false) {
			$data['data'] = 0;
		}
		else {
			$data['data'] = $return;
		}
		
		returnData($returnType, $data);
		return;
	}
}

/**
 * Add event commet.
 *
 * @param $id event id.
 * @param $thrash2 Don't use.
 * @param array $other it's array, but only <csv_separator> is available.
 * @param $thrash3 Don't use.
 *
 * example:
 *   http://127.0.0.1/pandora_console/include/api.php?op=set&op2=add_event_comment&id=event_id&other=string|&other_mode=url_encode_separator_|&apipass=1234&user=admin&pass=pandora
 */
function api_set_add_event_comment($id, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($other['type'] == 'string') {
		returnError('error_parameter', 'Error in the parameters.');
		return;
	}
	else if ($other['type'] == 'array') {
		$comment = io_safe_input($other['data'][0]);
		$meta = $other['data'][1];
		$history = $other['data'][2];
		
		$status = events_comment($id, $comment, 'Added comment', $meta,
			$history);
		if (is_error($status)) {
			returnError('error_add_event_comment',
				__('Error adding event comment.'));
			return;
		}
	}
	
	returnData('string', array('type' => 'string', 'data' => $status));
	return;
}

// http://localhost/pandora_console/include/api.php?op=get&op2=tactical_view&apipass=1234&user=admin&pass=pandora
function api_get_tactical_view($trash1, $trash2, $trash3, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$tactical_info = reporting_get_group_stats();
	
	switch ($returnType) {
		case 'string':
			$i = 0;
			foreach ($tactical_info as $key => $data) {
				if ($i == 0)
					$result = $key . ': ' . $data . '<br>';
				else
					$result .= $key . ': ' . $data . '<br>'; 
				
				$i++;
			}
			
			$data = array('type' => 'string', 'data' => $result);
			break;
		case 'csv':
			$data = array('type' => 'array', 'data' => array($tactical_info));
			break;
	}
	
	returnData($returnType, $data);
	return;
	
}

// http://localhost/pandora_console/include/api.php?op=get&op2=netflow_get_data&other=1348562410|1348648810|0|base64_encode(json_encode($filter))|none|50|bytes&other_mode=url_encode_separator_|&apipass=pandora&user=pandora&pass=pandora'
function api_get_netflow_get_data ($discard_1, $discard_2, $params) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	// Parse function parameters
	$start_date = $params['data'][0];
	$end_date = $params['data'][1];
	$interval_length = $params['data'][2];
	$filter = json_decode (base64_decode ($params['data'][3]), true);
	$aggregate = $params['data'][4];
	$max = $params['data'][5];
	$unit = $params['data'][6];
	$address_resolution = $params['data'][7];
	
	// Get netflow data
	$data = netflow_get_data ($start_date, $end_date, $interval_length, $filter, $aggregate, $max, $unit, '', $address_resolution);
	
	returnData('json', $data);
	return;
}

// http://localhost/pandora_console/include/api.php?op=get&op2=netflow_get_stats&other=1348562410|1348648810|base64_encode(json_encode($filter))|none|50|bytes&other_mode=url_encode_separator_|&apipass=pandora&user=pandora&pass=pandora'
function api_get_netflow_get_stats ($discard_1, $discard_2, $params) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	// Parse function parameters
	$start_date = $params['data'][0];
	$end_date = $params['data'][1];
	$filter = json_decode (base64_decode ($params['data'][2]), true);
	$aggregate = $params['data'][3];
	$max = $params['data'][4];
	$unit = $params['data'][5];
	$address_resolution = $params['data'][6];
	
	// Get netflow data
	$data = netflow_get_stats ($start_date, $end_date, $filter, $aggregate, $max, $unit, '', $address_resolution);
	
	returnData('json', $data);
	return;
}

// http://localhost/pandora_console/include/api.php?op=get&op2=netflow_get_summary&other=1348562410|1348648810|_base64_encode(json_encode($filter))&other_mode=url_encode_separator_|&apipass=pandora&user=pandora&pass=pandora'
function api_get_netflow_get_summary ($discard_1, $discard_2, $params) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	// Parse function parameters
	$start_date = $params['data'][0];
	$end_date = $params['data'][1];
	$filter = json_decode (base64_decode ($params['data'][2]), true);
	
	// Get netflow data
	$data = netflow_get_summary ($start_date, $end_date, $filter);
	returnData('json', $data);
	return;
}

//http://localhost/pandora_console/include/api.php?op=set&op2=validate_event_by_id&id=23&apipass=1234&user=admin&pass=pandora
function api_set_validate_event_by_id ($id, $trash1, $trash2, $returnType) {
	global $config;
	
	$data['type'] = 'string';
	$check_id = db_get_value('id_evento', 'tevento', 'id_evento', $id);
	
	if ($check_id) { //event exists
		
		$status = db_get_value('estado', 'tevento', 'id_evento', $id);
		if ($status == 1) { //event already validated
			$data['data'] = "Event already validated";
		}
		else {
			$ack_utimestamp = time();
			
			events_comment($id, '', "Change status to validated");
			
			$values = array(
				'ack_utimestamp' => $ack_utimestamp,
				'estado' => 1
				);
			
			$result = db_process_sql_update('tevento', $values, array('id_evento' => $id));
			
			if ($result === false) {
				$data['data'] = "Error validating event";
			}
			else {
				$data['data'] = "Event validate";
			}
		}
		
	}
	else {
		$data['data'] = "Event not exists";
	}
	
	returnData($returnType, $data);
	return;
}

/**
 * 
 * @param $trash1
 * @param $trash2
 * @param array $other it's array, but only <csv_separator> is available.
 * @param $returnType
 *
 */
//  http://localhost/pandora_console/include/api.php?op=get&op2=pandora_servers&return_type=csv&apipass=1234&user=admin&pass=pandora
function api_get_pandora_servers($trash1, $trash2, $other, $returnType) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if (!isset($other['data'][0]))
		$separator = ';'; // by default
	else
		$separator = $other['data'][0];
	
	$servers = servers_get_info ();
	
	foreach ($servers as $server) {
		$dd = array (
			'name' => $server["name"],
			'status' => $server["status"],
			'type' => $server["type"],
			'master' => $server["master"],
			'modules' => $server["modules"],
			'modules_total' => $server["modules_total"],
			'lag' => $server["lag"],
			'module_lag' => $server["module_lag"],
			'threads' => $server["threads"],
			'queued_modules' => $server["queued_modules"],
			'keepalive' => $server['keepalive'],
			'id_server' => $server['id_server']
		);
		
		// servers_get_info() returns "<a http:....>servername</a>" for recon server's name.
		// i don't know why and the following line is a temprary workaround... 
		$dd["name"] = preg_replace( '/<[^>]*>/', "", $dd["name"]);
		
		switch ($dd['type']) {
			case "snmp":
			case "event":
				$dd['modules'] = '';
				$dd['modules_total'] = '';
				$dd['lag'] = '';
				$dd['module_lag'] = '';
				break;
			case "export":
				$dd['lag'] = '';
				$dd['module_lag'] = '';
				break;
			default:
				break;
		}
		
		$returnVar[] = $dd;
	}
	
	$data = array('type' => 'array', 'data' => $returnVar);
	
	returnData($returnType, $data, $separator);
	return;
}

/**
 * Enable/Disable agent given an id
 * 
 * @param string $id String Agent ID
 * @param $thrash2 not used.
 * @param array $other it's array, $other as param is <enable/disable value> in this order and separator char
 *  (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 *  example:
 * 
 * 	example 1 (Enable agent 'example_id') 
 * 
 *  api.php?op=set&op2=enable_disable_agent&id=example_id&other=0&other_mode=url_encode_separator_|
 *  
 * 	example 2 (Disable agent 'example_id')
 * 
 *  api.php?op=set&op2=enable_disable_agent&id=example_id16&other=1&other_mode=url_encode_separator_|
 * 
 * @param $thrash3 Don't use.
 */

function api_set_enable_disable_agent ($id, $thrash2, $other, $thrash3) {
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id == ""){
		returnError('error_enable_disable_agent', 'Error enable/disable agent. Id_agent cannot be left blank.');
		return;
	}
	
	
	if ($other['data'][0] != "0" and $other['data'][0] != "1"){
		returnError('error_enable_disable_agent', 'Error enable/disable agent. Enable/disable value cannot be left blank.');
		return;
	}
	
	if (agents_get_name($id) == false){
		returnError('error_enable_disable_agent', 'Error enable/disable agent. The agent doesn\'t exists.');
		return;
	}

	$disabled = ( $other['data'][0] ? 0 : 1 );
	
	$result = db_process_sql_update('tagente', array('disabled' => $disabled), array('id_agente' => $id));
	
	if (is_error($result)) {
		// TODO: Improve the error returning more info
		returnError('error_enable_disable_agent', __('Error in agent enabling/disabling.'));
	}
	else {
		if ($disabled == 0){
			returnData('string', array('type' => 'string', 'data' => __('Enabled agent.')));
		}
		else {
			returnData('string', array('type' => 'string', 'data' => __('Disabled agent.')));			
		}
	}
}

/**
 * Validate alert from Pager Duty service. This call will be setted in PagerDuty's service as a Webhook to 
 * validate the alerts of Pandora FMS previously linked to PagertDuty when its were validated from PagerDuty.
 * 
 * This call only have a parameter: id=alert
 * 
 * Call example:
 * 	http://127.0.0.1/pandora_console/include/api.php?op=set&op2=pagerduty_webhook&apipass=1234&user=admin&pass=pandora&id=alert
 * 
 * TODO: Add support to events.
 * 
 */
 
function api_set_pagerduty_webhook($type, $matchup_path, $tresh2, $return_type) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$pagerduty_data = json_decode(file_get_contents('php://input'), true);
	
	foreach($pagerduty_data['messages'] as $pm) {
		$incident = $pm['data']['incident'];
		$incident_type = $pm['type'];
		// incident.acknowledge
		// incident.resolve
		// incident.trigger

		switch($type) {
			case 'alert':
				// Get all the alerts that the user can see
				$id_groups = array_keys(users_get_groups($config["id_user"], 'AR', false));
				$alerts = get_group_alerts($id_groups);

				// When an alert is resolved, the Pandoras alert will be validated
				if ($incident_type != 'incident.resolve') {
					break;
				}

				$alert_id = 0;
				foreach($alerts as $al) {
	    				$key = file_get_contents($matchup_path . '/.pandora_pagerduty_id_' . $al['id']);
					if ($key == $incident['incident_key']) {
						$alert_id = $al['id'];
						break;
					}
				}

				if ($alert_id != 0) {
					alerts_validate_alert_agent_module($alert_id);
				}
				break;
			case 'event':
				break;
		}
	}
}

/**
 * Get special days, and print all the result like a csv.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, but only <csv_separator> is available.
 * @param $thrash3 Don't use.
 *
 * example:
 *  api.php?op=get&op2=special_days&other=,;
 *
 */
function api_get_special_days($thrash1, $thrash2, $other, $thrash3) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if (!isset($other['data'][0]))
		$separator = ';'; // by default
	else
		$separator = $other['data'][0];
	
	$filter = false;
	
	$special_days = @db_get_all_rows_filter ('talert_special_days', $filter);
	
	if ($special_days !== false) {
		$data['type'] = 'array';
		$data['data'] = $special_days;
	}
	
	if (!$special_days) {
		returnError('error_get_special_days', __('Error getting special_days.'));
	}
	else {
		returnData('csv', $data, $separator);
	}
}

/**
 * Create a special day. And return the id if new special day.
 *
 * @param $thrash1 Don't use.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <special_day>;<same_day>;<description>;<id_group>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 *  api.php?op=set&op2=create_special_day&other=2014-05-03|sunday|text|0&other_mode=url_encode_separator_|
 *
 */
function api_set_create_special_day($thrash1, $thrash2, $other, $thrash3) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
	$special_day = $other['data'][0];
	$same_day = $other['data'][1];
	$description = $other['data'][2];
	$idGroup = $other['data'][3];
	
	$check_id_special_day = db_get_value ('id', 'talert_special_days', 'date', $special_day);
	
	if ($check_id_special_day) {
		returnError('error_create_special_day', __('Error creating special day. Specified day already exists.'));
		return;
	}
	
	if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $special_day)) {
		returnError('error_create_special_day', __('Error creating special day. Invalid date format.'));
		return;
	}
	
	$values = array(
		'description' => $other['data'][2],
		'id_group' => $other['data'][3],
	);
	
	$idSpecialDay = alerts_create_alert_special_day($special_day, $same_day, $values);
	
	if (is_error($idSpecialDay)) {
		returnError('error_create_special_day', __('Error in creation special day.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $idSpecialDay));
	}
}

/**
 * Update a special day. And return a message with the result of the operation.
 *
 * @param string $id Id of the special day to update.
 * @param $thrash2 Don't use.
 * @param array $other it's array, $other as param is <special_day>;<same_day>;<description>;<id_group>; in this order
 *  and separator char (after text ; ) and separator (pass in param othermode as othermode=url_encode_separator_<separator>)
 * @param $thrash3 Don't use
 *
 * example:
 *  api.php?op=set&op2=update_special_day&id=1&other=2014-05-03|sunday|text|0&other_mode=url_encode_separator_|
 *
 */
function api_set_update_special_day($id_special_day, $thrash2, $other, $thrash3) {
	global $config;
	
	if (defined ('METACONSOLE')) {
		return;
	}
	
        $special_day = $other['data'][0];
        $same_day = $other['data'][1];
        $description = $other['data'][2];
        $idGroup = $other['data'][3];

	if ($id_special_day == "") {
		returnError('error_update_special_day', __('Error updating special day. Id cannot be left blank.'));
		return;
        }
        
	$check_id_special_day = db_get_value ('id', 'talert_special_days', 'id', $id_special_day);
   
        if (!$check_id_special_day) {
		returnError('error_update_special_day', __('Error updating special day. Id doesn\'t exists.'));
		return;
	}

	if (!preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $special_day)) {
		returnError('error_update_special_day', __('Error updating special day. Invalid date format.'));
		return;	
	}
 
        $return = db_process_sql_update('talert_special_days',
                array('date' => $special_day,
                        'same_day' => $same_day,
                        'description' => $description,
                        'id_group' => $idGroup),
                array('id' => $id_special_day));

        returnData('string',
                array('type' => 'string', 'data' => (int)((bool)$return)));
}

/**
 * Delete a special day. And return a message with the result of the operation.
 *
 * @param string $id Id of the special day to delete.
 * @param $thrash2 Don't use.
 * @param $thrash3 Don't use.
 * @param $thrash4 Don't use.
 *
 * example:
 *  api.php?op=set&op2=delete_special_day&id=1
 *
 */
function api_set_delete_special_day($id_special_day, $thrash2, $thrash3, $thrash4) {
	if (defined ('METACONSOLE')) {
		return;
	}
	
	if ($id_special_day == "") {
		returnError('error_update_special_day', __('Error deleting special day. Id cannot be left blank.'));
		return;
	}
	
	$check_id_special_day = db_get_value ('id', 'talert_special_days', 'id', $id_special_day);
	
	if (!$check_id_special_day) {
		returnError('error_delete_special_day', __('Error deleting special day. Id doesn\'t exists.'));
		return;
	}
	
	$return = alerts_delete_alert_special_day ($id_special_day);
	
	if (is_error($return)) {
		returnError('error_delete_special_day', __('Error in deletion special day.'));
	}
	else {
		returnData('string', array('type' => 'string', 'data' => $return));
	}
}

?>
