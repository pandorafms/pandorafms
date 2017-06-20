<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2017 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
include_once($config['homedir'] . "/include/functions_config.php");
include_once($config['homedir'] . "/include/functions_snmp_browser.php");
require_once ($config['homedir'] . '/include/functions_network_components.php');

global $config;

if(is_ajax()){
	ob_clean();
	$action = (string) get_parameter ("action", "");
	$target_ip = (string) get_parameter ("target_ip", '');
	$community = (string) get_parameter ("community", '');
	$snmp_version = (string) get_parameter ("snmp_browser_version", '');
	$snmp3_auth_user = get_parameter('snmp3_browser_auth_user');
	$snmp3_security_level = get_parameter('snmp3_browser_security_level');
	$snmp3_auth_method = get_parameter('snmp3_browser_auth_method');
	$snmp3_auth_pass = get_parameter('snmp3_browser_auth_pass');
	$snmp3_privacy_method = get_parameter('snmp3_browser_privacy_method');
	$snmp3_privacy_pass = get_parameter('snmp3_browser_privacy_pass');
	
	$targets_oids = get_parameter ("oids", "");
	$targets_oids = explode(",", $targets_oids);
	
	
	$custom_action = get_parameter ("custom_action", "");
	if ($custom_action != "") {
		$custom_action = urldecode (base64_decode ($custom_action));
	}
	if($action == 'create_modules_snmp'){
		$fail_modules = array();
		foreach ($targets_oids as $key => $target_oid) {
			$oid = snmp_browser_get_oid ($target_ip, $community,
				htmlspecialchars_decode($target_oid), $snmp_version, $snmp3_auth_user,
				$snmp3_security_level, $snmp3_auth_method, $snmp3_auth_pass,
				$snmp3_privacy_method, $snmp3_privacy_pass);
			
				
			$name_check = db_get_value ('name', 'tnetwork_component',
				'name', $oid['oid']);
			
			if(empty($oid['description'])) {
				$description = '';
			} else {
				$description = io_safe_input($oid['description']);
			}
			
			if(!$name_check){
				$id = network_components_create_network_component ($oid['oid'],17,1, 
			        array ('description' => $description,
			            'module_interval' => 300,
			            'max' => 0,
			            'min' => 0,
			            'tcp_send' => $snmp_version,
			            'tcp_rcv' => '',
			            'tcp_port' => 0,
			            'snmp_oid' => $oid['numeric_oid'],
			            'snmp_community' => $community,
			            'id_module_group' => 3,
			            'id_modulo' => 2,
			            'id_plugin' => 0,
			            'plugin_user' => '',
			            'plugin_pass' => '',
			            'plugin_parameter' => '',
			            'macros' => '',
			            'max_timeout' => 0,
			            'max_retries' => 0,
			            'history_data' => '',
			            'dynamic_interval' => 0,
			            'dynamic_max' => 0,
			            'dynamic_min' => 0,
			            'dynamic_two_tailed' => 0,
			            'min_warning' => 0,
			            'max_warning' => 0,
			            'str_warning' => '',
			            'min_critical' => 0,
			            'max_critical' => 0,
			            'str_critical' => '',
			            'min_ff_event' => 0,
			            'custom_string_1' => '',
			            'custom_string_2' => '',
			            'custom_string_3' => '',
			            'post_process' => 0,
			            'unit' => '',
			            'wizard_level' => 'nowizard',
			            'macros' => '',
			            'critical_instructions' => '',
			            'warning_instructions' => '',
			            'unknown_instructions' => '',
			            'critical_inverse' => 0,
			            'warning_inverse' => 0,
			            'id_category' => 0,
			            'tags' => '',
			            'disabled_types_event' => '{"going_unknown":1}',
			            'min_ff_event_normal' => 0,
			            'min_ff_event_warning' => 0,
			            'min_ff_event_critical' => 0,
			            'each_ff' => 0));
			}
			
			if(empty($id)) {
				array_push($fail_modules,$name_check);
			}
		}
	}
	
	echo json_encode($fail_modules);
	return;
}



?>