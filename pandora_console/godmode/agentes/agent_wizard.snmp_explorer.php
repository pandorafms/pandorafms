<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;
include_once($config['homedir'] . "/include/functions_agents.php");
require_once ('include/functions_modules.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_reporting.php');
require_once ('include/graphs/functions_utils.php');

// Define a separator to implode/explode data
$separator = '_.._';
	
$idAgent = (int) get_parameter('id_agente', 0);
$ipAgent = db_get_value('direccion', 'tagente', 'id_agente', $idAgent);

check_login ();

$ip_target = (string) get_parameter ('ip_target', $ipAgent);
$use_agent = get_parameter ('use_agent');
$snmp_community = (string) get_parameter ('snmp_community', 'public');
$snmp_version = get_parameter('snmp_version', '1');
$snmp3_auth_user = get_parameter('snmp3_auth_user');
$snmp3_security_level = get_parameter('snmp3_security_level');
$snmp3_auth_method = get_parameter('snmp3_auth_method');
$snmp3_auth_pass = get_parameter('snmp3_auth_pass');
$snmp3_privacy_method = get_parameter('snmp3_privacy_method');
$snmp3_privacy_pass = get_parameter('snmp3_privacy_pass');
$tcp_port = (string) get_parameter ('tcp_port');

//See if id_agente is set (either POST or GET, otherwise -1
$id_agent = $idAgent;

// Get passed variables
$snmpwalk = (int) get_parameter("snmpwalk", 0);
$create_modules = (int) get_parameter("create_modules", 0);

// Get the plugin
switch ($config['dbtype']) {
	case 'mysql':
	case 'postgresql':
		$plugin = db_get_row_sql("SELECT id, macros FROM tplugin WHERE execute LIKE '%/snmp_remote.pl'");
		break;
	case 'oracle':
		$plugin = db_get_row_sql("SELECT id, TO_CHAR(macros) AS macros FROM tplugin WHERE execute LIKE '%/snmp_remote.pl'");
		break;
}


if (empty($plugin)) {
	ui_print_info_message(array('message' => __('The SNMP remote plugin doesnt seem to be installed') . '. ' . __('It is necessary to use some features') . '.<br><br>' . __('Please, install the SNMP remote plugin (The name of the plugin must be snmp_remote.pl)'), 'no_close' => true));
}

// Define STATIC SNMP data
$static_snmp_descriptions = array(
	'Load-1' => 'Load Average (Last minute)',
	'Load-5' => 'Load Average (Last 5 minutes)',
	'Load-15' => 'Load Average (Last 5 minutes)',
	'memTotalSwap' => 'Total Swap Size configured for the host',
	'memAvailSwap' => 'Available Swap Space on the host',
	'memTotalReal' => 'Total Real/Physical Memory Size on the host',
	'memAvailReal' => 'Available Real/Physical Memory Space on the host',
	'memTotalFree' => 'Total Available Memory on the host',
	//'memShared' => 'Total Shared Memory',
	'memCached' => 'Total Cached Memory',
	'memBuffer' => 'Total Buffered Memory',
	'ssSwapIn' => 'Amount of memory swapped in from disk (kB/s)',
	'ssSwapOut' => 'Amount of memory swapped to disk (kB/s)',
	'ssIORawSent' => 'Number of blocks sent to a block device',
	'ssIORawReceived' => 'Number of blocks received from a block device',
	'ssRawInterrupts' => 'Number of interrupts processed',
	'ssRawContexts' => 'Number of context switches',
	'ssCpuRawUser' => 'user CPU time',
	'ssCpuRawSystem' => 'system CPU time',
	'ssCpuRawIdle' => 'idle CPU time',
	'sysUpTime' => 'system Up time');

$static_snmp_oids = array(
	'Load-1' => '.1.3.6.1.4.1.2021.10.1.5.1',
	'Load-5' => '.1.3.6.1.4.1.2021.10.1.5.2',
	'Load-15' => '.1.3.6.1.4.1.2021.10.1.5.3',
	'memTotalSwap' => '.1.3.6.1.4.1.2021.4.3.0',
	'memAvailSwap' => '.1.3.6.1.4.1.2021.4.4.0',
	'memTotalReal' => '.1.3.6.1.4.1.2021.4.5.0',
	'memAvailReal' => '.1.3.6.1.4.1.2021.4.6.0',
	'memTotalFree' => '.1.3.6.1.4.1.2021.4.11.0',
	//'memShared' => '.1.3.6.1.4.1.2021.4.13',
	'memCached' => '.1.3.6.1.4.1.2021.4.15.0',
	'memBuffer' => '.1.3.6.1.4.1.2021.4.14.0',
	'ssSwapIn' => '.1.3.6.1.4.1.2021.11.3.0',
	'ssSwapOut' => '.1.3.6.1.4.1.2021.11.4.0',
	'ssIORawSent' => '.1.3.6.1.4.1.2021.11.57.0',
	'ssIORawReceived' => '.1.3.6.1.4.1.2021.11.58.0',
	'ssRawInterrupts' => '.1.3.6.1.4.1.2021.11.59.0',
	'ssRawContexts' => '.1.3.6.1.4.1.2021.11.60.0',
	'ssCpuRawUser' => '.1.3.6.1.4.1.2021.11.50.0',
	'ssCpuRawSystem' => '.1.3.6.1.4.1.2021.11.52.0',
	'ssCpuRawIdle' => '.1.3.6.1.4.1.2021.11.53.0',
	'sysUpTime' => '1.3.6.1.2.1.1.3.0');

$static_snmp_post_process = array(
	'sysUpTime' => "0.00000011574074");

// Using plugin
if (!empty($plugin)) {
	$static_snmp_descriptions['avgCpuLoad'] = 'Average of CPUs Load (%)';
	$static_snmp_descriptions['memoryUse'] = 'Memory use (%)';
}

$fail = false;

$devices = array();
$processes = array();
$disks = array();
$temperatures = array();

$arrow = false;

$other_snmp_data = array();

if ($snmpwalk) {
	// OID Used is for DISKS
	$snmpis = get_snmpwalk($ip_target, $snmp_version, $snmp_community, $snmp3_auth_user,
		$snmp3_security_level, $snmp3_auth_method, $snmp3_auth_pass,
		$snmp3_privacy_method, $snmp3_privacy_pass, 0, ".1.3.6.1.2.1.25.2.3.1.3", $tcp_port);
	
	if (empty($snmpis)) {
		$fail = true;
		$snmpis = array();
	}
	else {
		// We get here only the interface part of the MIB, not full mib
		foreach($snmpis as $key => $snmp) {
			
			$data = explode(': ',$snmp);
			$keydata = explode('::',$key);
			$keydata2 = explode('.',$keydata[1]);
			
			// Avoid results without index and results without name
			if (!isset($keydata2[1]) || !isset($data[1])) {
				continue;
			}
			
			
			if (array_key_exists(1,$data)) {
				$disks[$data[1]] = $data[1];
			
			}
			else {
				$disks[$data[0]] = $data[0];
				
			}
		}
		
		// OID Used is for PROCESSES
		$snmpis = get_snmpwalk($ip_target, $snmp_version, $snmp_community, $snmp3_auth_user,
			$snmp3_security_level, $snmp3_auth_method, $snmp3_auth_pass,
			$snmp3_privacy_method, $snmp3_privacy_pass, 0, ".1.3.6.1.2.1.25.4.2.1.2", $tcp_port);
		
		if ($snmpis === false) {
			$snmpis = array();
		}
		
		
		// We get here only the interface part of the MIB, not full mib
		foreach($snmpis as $key => $snmp) {
			
			$data = explode(': ',$snmp);
			$keydata = explode('::',$key);
			$keydata2 = explode('.',$keydata[1]);
			
			// Avoid results without index and results without name
			if (!isset($keydata2[1]) || !isset($data[1])) {
				continue;
			}
			
			if (array_key_exists(1,$data)) {
				$process_name = str_replace  ( "\""  , "" , $data[1]);
				
			}
			else {
				$process_name = str_replace  ( "\""  , "" , $data[0]);
				
			}
			
			$processes[$process_name] = $process_name;
		}
		
		// Keep only the first process found
		$processes = array_unique($processes);
		
		
		// OID Used is for SENSOR TEMPERATURES
		$snmpis = get_snmpwalk($ip_target, $snmp_version, $snmp_community, $snmp3_auth_user,
			$snmp3_security_level, $snmp3_auth_method, $snmp3_auth_pass,
			$snmp3_privacy_method, $snmp3_privacy_pass, 0, ".1.3.6.1.4.1.2021.13.16.2.1", $tcp_port);
		
		if ($snmpis === false) {
			$snmpis = array();
		}
		
		
		// We get here only the interface part of the MIB, not full mib
		foreach($snmpis as $key => $snmp) {
			
			$data = explode(': ',$snmp);
			$keydata = explode('::',$key);
			$keydata2 = explode('.',$keydata[1]);
			
			// Avoid results without index and results without name
			if (!isset($keydata2[1]) || !isset($data[1])) {
				continue;
			}
			
			
			if ($keydata2[0] == 'lmTempSensorsDevice') {
				if (array_key_exists(1,$data)) {
					$temperatures[$keydata2[1]] = $data[1];
					
				}
				else {
					$temperatures[$keydata2[1]] = $data[0];
					
				}
			}
		}
		
		// Keep only the first sensor found
		$temperatures = array_unique($temperatures);
		
		// OID Used is for DEVICES
		$snmpis = get_snmpwalk($ip_target, $snmp_version, $snmp_community, $snmp3_auth_user,
			$snmp3_security_level, $snmp3_auth_method, $snmp3_auth_pass,
			$snmp3_privacy_method, $snmp3_privacy_pass, 0, ".1.3.6.1.4.1.2021.13.15.1.1", $tcp_port);
		
		if ($snmpis === false) {
			$snmpis = array();
		}
		
		
		// We get here only the interface part of the MIB, not full mib
		foreach($snmpis as $key => $snmp) {
			
			$data = explode(': ',$snmp);
			$keydata = explode('::',$key);
			$keydata2 = explode('.',$keydata[1]);
			
			// Avoid results without index and results without name
			if (!isset($keydata2[1]) || !isset($data[1])) {
				continue;
			}
			
			
			if ($keydata2[0] == 'diskIODevice') {
				if (array_key_exists(1,$data)) {
					$devices['diskIONRead' . $separator . $keydata2[1]] = $data[1] . ' - Bytes read';
					$devices['diskIONWritten' . $separator . $keydata2[1]] = $data[1] . ' - Bytes written';
					$devices['diskIONReads' . $separator . $keydata2[1]] = $data[1] . ' - Read accesses';
					$devices['diskIONWrites' . $separator . $keydata2[1]] = $data[1] . ' - Write accesses';
				}
				else {
					$devices['diskIONRead' . $separator . $keydata2[1]] = $data[0] . ' - Bytes read';
					$devices['diskIONWritten' . $separator . $keydata2[1]] = $data[0] . ' - Bytes written';
					$devices['diskIONReads' . $separator . $keydata2[1]] = $data[0] . ' - Read accesses';
					$devices['diskIONWrites' . $separator . $keydata2[1]] = $data[0] . ' - Write accesses';
				}
			}
		}
	}
	
	// Other SNMP Data
	$arrow = true;
	
	foreach ($static_snmp_oids as $key => $oid) {
		if ($snmp_version == 3) {
			$result = false; //It is statics oids.
		}
		else {
			$result = snmpget($ip_target, $snmp_community, $oid);
		}
		
		if ($result != false) {
			$other_snmp_data[$key] = $static_snmp_descriptions[$key];
		}
	}
	if (empty($other_snmp_data)) {
		$arrow = false;
		$other_snmp_data[0] = __('Remote system doesnt support host SNMP information');
	}
	
}

if ($create_modules) {
	$modules = get_parameter("module", array());
	
	$devices = array();
	$processes = array();
	$disks = array();
	$temperatures = array();
	$snmpdata = array();
	
	foreach ($modules as $module) {
		// Split module data to get type
		$module_exploded = explode($separator, $module);
		$type = $module_exploded[0];
		
		// Delete type from module data
		unset($module_exploded[0]);
		
		// Rebuild module data
		$module = implode($separator, $module_exploded);
		
		switch($type) {
			case 'device':
				$devices[] = $module;
				break;
			case 'process':
				$processes[] = $module;
				break;
			case 'disk':
				$disks[] = $module;
				break;
			case 'temperature':
				$temperatures[] = $module;
				break;
			case 'snmpdata':
				$snmpdata[] = $module;
				break;
		}
	}
	
	if (agents_get_name($id_agent) == false) {
		ui_print_error_message (__('No agent selected or the agent does not exist'));
	}
	else {
		
		// Common values
		$common_values = array();
		
		if ($tcp_port != '') {
			$common_values['tcp_port'] = $tcp_port;
		}
		$common_values['snmp_community'] = $snmp_community;
		if($use_agent){
			$common_values['ip_target'] = 'auto';
		}
		else{
			$common_values['ip_target'] = $ip_target;	
		}
		
		$common_values['tcp_send'] = $snmp_version;
		
		if ($snmp_version == '3') {
			$common_values['plugin_user'] = $snmp3_auth_user;
			$common_values['plugin_pass'] = $snmp3_auth_pass;
			$common_values['plugin_parameter'] = $snmp3_auth_method;
			$common_values['custom_string_1'] = $snmp3_privacy_method;
			$common_values['custom_string_2'] = $snmp3_privacy_pass;
			$common_values['custom_string_3'] = $snmp3_security_level;
		}
		
		
		// DEVICES
		$devices_prefix_oids = array(
			'diskIONRead' => '.1.3.6.1.4.1.2021.13.15.1.1.3.',
			'diskIONWritten' => '.1.3.6.1.4.1.2021.13.15.1.1.4.',
			'diskIONReads' => '.1.3.6.1.4.1.2021.13.15.1.1.5.',
			'diskIONWrites' => '.1.3.6.1.4.1.2021.13.15.1.1.6.'
			);
		
		$devices_prefix_descriptions = array(
			'diskIONRead' => 'The number of bytes read from this device since boot',
			'diskIONWritten' => 'The number of bytes written to this device since boot',
			'diskIONReads' => 'The number of read accesses from this device since boot',
			'diskIONWrites' => 'The number of write accesses from this device since boot'
			);
		
		$results = array();
		
		foreach ($devices as $device) {
			$module_values = $common_values;
			
			// Split module data to get type, name, etc
			$device_exploded = explode($separator, $device);
			$device_name = $device_exploded[0];
			
			$name_exploded = explode('-', $device_name);
			$name = ltrim(html_entity_decode($name_exploded[1]));
			
			$device_type = $device_exploded[1];
			
			// Delete type from device id
			unset($device_exploded[0]);
			unset($device_exploded[1]);
			
			// Rebuild device_name
			$device_id = implode($separator, $device_exploded);
			
			$module_values['descripcion'] = $devices_prefix_descriptions[$device_type];
			
			if (($name == 'Bytes read') || ($name == 'Bytes written')) {
				$module_values['id_tipo_modulo'] = modules_get_type_id('remote_snmp_inc');
			}
			else {
				$module_values['id_tipo_modulo'] = modules_get_type_id('remote_snmp');
			}
			
			$module_values['snmp_oid'] = $devices_prefix_oids[$device_type] . $device_id;
			
			$module_values['id_modulo'] = MODULE_SNMP;
			
			$result = modules_create_agent_module ($id_agent, io_safe_input($device_name), $module_values);
			
			$results[$result][] = $device_name;
		} 
		
		// TEMPERATURE SENSORS
		$temperatures_prefix_oid = '.1.3.6.1.4.1.2021.13.16.2.1.3.';
		$temperatures_description = 'The temperature of this sensor in C';
		
		foreach ($temperatures as $temperature) {
			$module_values = $common_values;
			
			// Split module data to get type, name, etc
			$temperature_exploded = explode($separator, $temperature);
			$temperature_name = $temperature_exploded[0];
			
			// Delete name from temperature sensor id
			unset($temperature_exploded[0]);
			
			// Rebuild device_name
			$temperature_id = implode($separator, $temperature_exploded);
			
			$module_values['descripcion'] = $temperatures_description;
			
			$module_values['id_tipo_modulo'] = modules_get_type_id('remote_snmp');
			
			$module_values['snmp_oid'] = $temperatures_prefix_oid . $temperature_id;
			
			$module_values['id_modulo'] = MODULE_SNMP;
			
			// Temperature are given in mC. Convert to Celsius
			$module_values['post_process'] = 0.001;
			
			$module_values['unit'] = 'C';
			
			$result = modules_create_agent_module ($id_agent, io_safe_input($temperature_name), $module_values);
			
			$results[$result][] = $temperature_name;
		}
		
		// SNMP DATA (STATIC MODULES)
		
		foreach ($snmpdata as $snmpdata_name) {
			$module_values = $common_values;
			
			$module_values['descripcion'] = $static_snmp_descriptions[$snmpdata_name];
			$module_values['id_tipo_modulo'] = modules_get_type_id('remote_snmp');
			if (isset($static_snmp_post_process[$snmpdata_name])) {
				$module_values['post_process'] = 
					$static_snmp_post_process[$snmpdata_name];
			}
			
			//Average use of CPUs is a plugin module
			switch ($snmpdata_name) {
				case 'avgCpuLoad':
				case 'memoryUse':
					$module_values['id_modulo'] = MODULE_PLUGIN;
					$module_values['id_plugin'] = $plugin['id'];
					
					// Avoid the return of a string containing the word 'null' if the macros column is not defined
					$macros = array();
					if (isset($plugin['macros']) && !empty($plugin['macros']))
						$macros = json_decode($plugin['macros'], true);
					
					foreach ($macros as $k => $macro) {
						switch($macro['macro']) {
							case '_field1_':
								// Field 1 is the IP Address
								$macros[$k]['value'] = $module_values['ip_target'];
								break;
							case '_field2_':
								// Field 2 is the community
								$macros[$k]['value'] = $module_values['snmp_community'];
								break;
							case '_field3_':
								// Field 3 is the plugin parameters
								switch ($snmpdata_name) {
									case 'avgCpuLoad':
										$macros[$k]['value'] = '-m cpuload';
										break;
									case 'memoryUse':
										$macros[$k]['value'] = '-m memuse';
										break;
								}
								
								if ($snmp_version == '3') {
									$macros[$k]['value'] .= " -v3 ";
									switch ($snmp3_security_level) {
										case "authNoPriv":
											$macros[$k]['value'] .= 
												' -u ' . $snmp3_auth_user .
												' -A ' . $snmp3_auth_pass .
												' -l ' . $snmp3_security_level .
												' -a ' . $snmp3_auth_method;
											break;
										case "noAuthNoPriv":
											$macros[$k]['value'] .= 
												' -u ' . $snmp3_auth_user .
												' -l ' . $snmp3_security_level;
											break;
										default:
											$macros[$k]['value'] .= 
												' -u ' . $snmp3_auth_user .
												' -A ' . $snmp3_auth_pass .
												' -l ' . $snmp3_security_level .
												' -a ' . $snmp3_auth_method .
												' -x ' . $snmp3_privacy_method .
												' -X ' . $snmp3_privacy_pass;
											break;
									}
								}
								break;
						}
					}
					
					if (!empty($macros))
						$module_values['macros'] = io_json_mb_encode($macros);
					
					unset($module_values['snmp_community']); //snmp_community
					unset($module_values['ip_target']); //ip_target
					unset($module_values['tcp_send']); //snmp_version
					break;
				default:
					$module_values['snmp_oid'] = $static_snmp_oids[$snmpdata_name];
					
					$module_values['id_modulo'] = MODULE_SNMP;
					break;
			}
			
			$result = modules_create_agent_module ($id_agent, io_safe_input($snmpdata_name), $module_values);
			
			$results[$result][] = $snmpdata_name;
		}
		
		// PROCESSES
		foreach ($processes as $process) {
			$module_values = $common_values;
			
			$module_values['descripcion'] = sprintf(__('Check if the process %s is running or not'), $process);
			$module_values['id_tipo_modulo'] = modules_get_type_id('remote_snmp_proc');
			$module_values['id_modulo'] = MODULE_PLUGIN;
			$module_values['id_plugin'] = $plugin['id'];
			
			// Avoid the return of a string containing the word 'null' if the macros column is not defined
			$macros = array();
			if (isset($plugin['macros']) && !empty($plugin['macros']))
				$macros = json_decode($plugin['macros'], true);
			
			foreach ($macros as $k => $macro) {
				switch($macro['macro']) {
					case '_field1_':
						// Field 1 is the IP Address
						$macros[$k]['value'] = $module_values['ip_target'];
						break;
					case '_field2_':
						// Field 2 is the community
						$macros[$k]['value'] = $module_values['snmp_community'];
						break;
					case '_field3_':
						// Field 3 is the plugin parameters
						$macros[$k]['value'] = io_safe_input('-m process -p "' . $process . '"');
						
						if ($snmp_version == '3') {
							$macros[$k]['value'] .= " -v3 ";
							switch ($snmp3_security_level) {
								case "authNoPriv":
									$macros[$k]['value'] .= 
										' -u ' . $snmp3_auth_user .
										' -A ' . $snmp3_auth_pass .
										' -l ' . $snmp3_security_level .
										' -a ' . $snmp3_auth_method;
									break;
								case "noAuthNoPriv":
									$macros[$k]['value'] .= 
										' -u ' . $snmp3_auth_user .
										' -l ' . $snmp3_security_level;
									break;
								default:
									$macros[$k]['value'] .= 
										' -u ' . $snmp3_auth_user .
										' -A ' . $snmp3_auth_pass .
										' -l ' . $snmp3_security_level .
										' -a ' . $snmp3_auth_method .
										' -x ' . $snmp3_privacy_method .
										' -X ' . $snmp3_privacy_pass;
									break;
							}
						}
						break;
				}
			}
			
			if (!empty($macros))
				$module_values['macros'] = io_json_mb_encode($macros);
			
			unset($module_values['snmp_community']); //snmp_community
			unset($module_values['ip_target']); //ip_target
			unset($module_values['tcp_send']); //snmp_version
			
			$result = modules_create_agent_module ($id_agent, io_safe_input($process), $module_values);
			
			$results[$result][] = $process;
		} 
		
		
		// DISKS USE
		foreach ($disks as $disk) {
			$module_values = $common_values;
			
			$module_values['descripcion'] = __('Disk use information');
			$module_values['id_tipo_modulo'] = modules_get_type_id('remote_snmp');
			$module_values['id_modulo'] = MODULE_PLUGIN;
			$module_values['id_plugin'] = $plugin['id'];
			
			// Avoid the return of a string containing the word 'null' if the macros column is not defined
			$macros = array();
			if (isset($plugin['macros']) && !empty($plugin['macros']))
				$macros = json_decode($plugin['macros'], true);
			
			foreach ($macros as $k => $macro) {
				switch($macro['macro']) {
					case '_field1_':
						// Field 1 is the IP Address
						$macros[$k]['value'] = $module_values['ip_target'];
						break;
					case '_field2_':
						// Field 2 is the community
						$macros[$k]['value'] = $module_values['snmp_community'];
						break;
					case '_field3_':
						// Field 3 is the plugin parameters
						$macros[$k]['value'] = io_safe_input('-m diskuse -d "' . io_safe_output($disk) . '"');
						
						if ($snmp_version == '3') {
							$macros[$k]['value'] .= " -v3 ";
							switch ($snmp3_security_level) {
								case "authNoPriv":
									$macros[$k]['value'] .= 
										' -u ' . $snmp3_auth_user .
										' -A ' . $snmp3_auth_pass .
										' -l ' . $snmp3_security_level .
										' -a ' . $snmp3_auth_method;
									break;
								case "noAuthNoPriv":
									$macros[$k]['value'] .= 
										' -u ' . $snmp3_auth_user .
										' -l ' . $snmp3_security_level;
									break;
								default:
									$macros[$k]['value'] .= 
										' -u ' . $snmp3_auth_user .
										' -A ' . $snmp3_auth_pass .
										' -l ' . $snmp3_security_level .
										' -a ' . $snmp3_auth_method .
										' -x ' . $snmp3_privacy_method .
										' -X ' . $snmp3_privacy_pass;
									break;
							}
						}
				}
			}
			
			if (!empty($macros))
				$module_values['macros'] = io_json_mb_encode($macros);
			
			unset($module_values['snmp_community']); //snmp_community
			unset($module_values['ip_target']); //ip_target
			unset($module_values['tcp_send']); //snmp_version
			
			$result = modules_create_agent_module($id_agent,
				io_safe_input($disk), $module_values);
			
			$results[$result][] = $disk;
		}
		
		$success_message = '';
		$error_message = '';
		
		if (isset($results[NOERR])) {
			if (count($results[NOERR]) > 0) {
				$success_message .= sprintf(__('%s modules created succesfully'), count($results[NOERR])) . '<br>';
			}
		}
		if (isset($results[ERR_GENERIC])) {
			if (count($results[ERR_GENERIC]) > 0) {
				$error_message .= sprintf(__('Error creating %s modules') . ': <br>&nbsp;&nbsp;* ' . implode('<br>&nbsp;&nbsp;* ', $results[ERR_GENERIC]), count($results[ERR_GENERIC])) . '<br>';
			}
		}
		if (isset($results[ERR_DB])) {
			if (count($results[ERR_DB]) > 0) {
				$error_message .= sprintf(__('Error creating %s modules') . ': <br>&nbsp;&nbsp;* ' . implode('<br>&nbsp;&nbsp;* ', $results[ERR_DB]), count($results[ERR_DB])) . '<br>';
			}
		}
		if (isset($results[ERR_EXIST])) {
			if (count($results[ERR_EXIST]) > 0) {
				$error_message .= sprintf(__('%s modules already exist') . ': <br>&nbsp;&nbsp;* ' . implode('<br>&nbsp;&nbsp;* ', $results[ERR_EXIST]), count($results[ERR_EXIST])) . '<br>';
			}
		}
		 
		if (!empty($error_message)) {
			ui_print_error_message($error_message);
		}
		else {
			if (empty($success_message)) {
				$success_message .= sprintf(__('Modules created succesfully')) . '<br>';
			}
			ui_print_success_message($success_message);
		}
	}
}

echo '<span id ="none_text" style="display: none;">' . __('None') . '</span>';
echo "<form method='post' id='walk_form' action='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=snmp_explorer&id_agente=$id_agent'>";

$table->width = '100%';
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->class = 'databox filters';

$table->data[0][0] = '<b>' . __('Target IP') . '</b>';
$table->data[0][1] = html_print_input_text ('ip_target', $ip_target, '', 15, 60, true);

$table->data[0][2] = '<b>' . __('Port') . '</b>';
$table->data[0][3] = html_print_input_text ('tcp_port', $tcp_port, '', 5, 20, true);

$table->data[1][0] = '<b>' . __('Use agent ip') . '</b>';
$table->data[1][1] = html_print_checkbox ('use_agent', 1, $use_agent, true);

$snmp_versions['1'] = 'v. 1';
$snmp_versions['2'] = 'v. 2';
$snmp_versions['2c'] = 'v. 2c';
$snmp_versions['3'] = 'v. 3';

$table->data[2][0] = '<b>' . __('SNMP community') . '</b>';
$table->data[2][1] = html_print_input_text ('snmp_community', $snmp_community, '', 15, 60, true);

$table->data[2][2] = '<b>' . __('SNMP version') . '</b>';
$table->data[2][3] = html_print_select ($snmp_versions, 'snmp_version', $snmp_version, '', '', '', true, false, false, '');

$table->data[2][3] .= '<div id="spinner_modules" style="float: left; display: none;">' . html_print_image("images/spinner.gif", true) . '</div>';

html_print_input_hidden('snmpwalk', 1);

html_print_table($table);

unset($table);

//SNMP3 OPTIONS 
$table->width = '100%';

$table->data[2][1] = '<b>'.__('Auth user').'</b>';
$table->data[2][2] = html_print_input_text ('snmp3_auth_user', $snmp3_auth_user, '', 15, 60, true);
$table->data[2][3] = '<b>'.__('Auth password').'</b>';
$table->data[2][4] = html_print_input_password ('snmp3_auth_pass', $snmp3_auth_pass, '', 15, 60, true);
$table->data[2][4] .= html_print_input_hidden('active_snmp_v3', 0, true);

$table->data[5][0] = '<b>'.__('Privacy method').'</b>';
$table->data[5][1] = html_print_select(array('DES' => __('DES'), 'AES' => __('AES')), 'snmp3_privacy_method', $snmp3_privacy_method, '', '', '', true);
$table->data[5][2] = '<b>'.__('privacy pass').'</b>';
$table->data[5][3] = html_print_input_password ('snmp3_privacy_pass', $snmp3_privacy_pass, '', 15, 60, true);

$table->data[6][0] = '<b>'.__('Auth method').'</b>';
$table->data[6][1] = html_print_select(array('MD5' => __('MD5'), 'SHA' => __('SHA')), 'snmp3_auth_method', $snmp3_auth_method, '', '', '', true);
$table->data[6][2] = '<b>'.__('Security level').'</b>';
$table->data[6][3] = html_print_select(array('noAuthNoPriv' => __('Not auth and not privacy method'),
	'authNoPriv' => __('Auth and not privacy method'), 'authPriv' => __('Auth and privacy method')), 'snmp3_security_level', $snmp3_security_level, '', '', '', true);

if ($snmp_version == 3) {
	echo '<div id="snmp3_options">';
}
else {
	echo '<div id="snmp3_options" style="display: none;">';
}
html_print_table($table);
echo '</div>';

echo "<div style='text-align:right; width:".$table->width."'>";
echo '<span id="oid_loading" class="invisible">' . html_print_image("images/spinner.gif", true) . '</span>';
html_print_submit_button(__('SNMP Walk'), 'snmp_walk', false, array('class' => 'sub next'));
echo "</div>";

if ($snmpwalk && $fail) {
	ui_print_error_message('<br>' . __('No data found') . '<br><br>' . __('If the device is a network device, try with the SNMP Interfaces wizard'));
}

unset($table);

echo "</form>";

if (!$fail) {
	echo '<span id ="none_text" style="display: none;">' . __('None') . '</span>';
	echo "<form method='post' action='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&wizard_section=snmp_explorer&id_agente=$id_agent'>";
	echo '<span id="form_interfaces">';

	html_print_input_hidden('create_modules', 1);
	html_print_input_hidden('ip_target', $ip_target);
	html_print_input_hidden('use_agent', $use_agent);
	html_print_input_hidden('tcp_port', $tcp_port);
	html_print_input_hidden('snmp_community', $snmp_community);
	html_print_input_hidden('snmp_version', $snmp_version);
	html_print_input_hidden('snmp3_auth_user', $snmp3_auth_user);
	html_print_input_hidden('snmp3_auth_pass', $snmp3_auth_pass);
	html_print_input_hidden('snmp3_auth_method', $snmp3_auth_method);
	html_print_input_hidden('snmp3_privacy_method', $snmp3_privacy_method);
	html_print_input_hidden('snmp3_privacy_pass', $snmp3_privacy_pass);
	html_print_input_hidden('snmp3_security_level', $snmp3_security_level);
	
	$table->width = '100%';
	
	// Mode selector
	$modes = array();
	$modes['devices'] = __('Devices');
	$modes['processes'] = __('Processes');
	$modes['disks'] = __('Free space on disk');
	$modes['temperatures'] = __('Temperature sensors');
	$modes['snmpdata'] = __('Other SNMP data');
	
	$table->data[1][0] = __('Wizard mode') . ': ';
	$table->data[1][0] .= html_print_select ($modes,
		'snmp_wizard_modes', '', '', '', '', true, false, false);
	$table->cellstyle[1][0] = 'vertical-align: middle;';
	
	$table->colspan[1][0] = 2;
	$table->data[1][2] = '<b>'.__('Modules').'</b>';
	$table->cellstyle[1][2] = 'vertical-align: middle;';
	
	// Devices list
	$table->data[2][0] = '<div class="wizard_mode_form wizard_mode_devices">';
	$table->data[2][0] .= html_print_select ($devices, 'devices', '', '',
		'', '', true, true, true, '', false, 'width: 300px;');
	$table->data[2][0] .= '</div>';
	
	// If SNMP remote plugin is not installed, show an advice
	if(empty($plugin)) {
		// Processes list
		$table->data[2][0] .= '<div class="wizard_mode_form wizard_mode_processes">';
		$table->data[2][0] .= ui_print_info_message(__('SNMP remote plugin is necessary for this feature'), '', true);
		$table->data[2][0] .= '</div>';
		
		// Disks list
		$table->data[2][0] .= '<div class="wizard_mode_form wizard_mode_disks">';
		$table->data[2][0] .= ui_print_info_message(__('SNMP remote plugin is necessary for this feature'), '', true);
		$table->data[2][0] .= '</div>';
	}
	else {
		// Processes list
		$table->data[2][0] .= '<div class="wizard_mode_form wizard_mode_processes">';
		$table->data[2][0] .= html_print_select ($processes, 'processes', '', '',
			'', '', true, true, true, '', false, 'width: 300px;');
		$table->data[2][0] .= '</div>';
		
		// Disks list
		$table->data[2][0] .= '<div class="wizard_mode_form wizard_mode_disks">';
		$table->data[2][0] .= html_print_select ($disks, 'disks', '', '',
			'', '', true, true, true, '', false, 'width: 300px;');
		$table->data[2][0] .= '</div>';
	}
	
	// Sensors temperatures list
	$table->data[2][0] .= '<div class="wizard_mode_form wizard_mode_temperatures">';
	$table->data[2][0] .= html_print_select ($temperatures, 'temperatures', '', '',
		'', '', true, true, true, '', false, 'width: 300px;');
	$table->data[2][0] .= '</div>';
	
	// SNMP data list
	$table->data[2][0] .= '<div class="wizard_mode_form wizard_mode_snmpdata">';
	$table->data[2][0] .= html_print_select ($other_snmp_data, 'snmpdata', '', '',
		'', '', true, true, true, '', false, 'width: 300px;');
	$table->data[2][0] .= '</div>';
	
	$table->cellstyle[2][0] = 'vertical-align: top; text-align: center;';
	
	// Devices arrow
	$table->data[2][1] = '<div class="wizard_mode_form wizard_mode_devices wizard_mode_devices_arrow clickable">' . html_print_image('images/darrowright.png', true, array('title' => __('Add to modules list'))) . '</div>';
	// Processes arrow
	$table->data[2][1] .= '<div class="wizard_mode_form wizard_mode_processes wizard_mode_processes_arrow clickable">' . html_print_image('images/darrowright.png', true, array('title' => __('Add to modules list'))) . '</div>';
	// Disks arrow
	$table->data[2][1] .= '<div class="wizard_mode_form wizard_mode_disks wizard_mode_disks_arrow clickable">' . html_print_image('images/darrowright.png', true, array('title' => __('Add to modules list'))) . '</div>';
	// Temperatures arrow
	$table->data[2][1] .= '<div class="wizard_mode_form wizard_mode_temperatures wizard_mode_temperatures_arrow clickable">' . html_print_image('images/darrowright.png', true, array('title' => __('Add to modules list'))) . '</div>';
	// SNMP data arrow
	if ($arrow) {
		$table->data[2][1] .= '<div class="wizard_mode_form wizard_mode_snmpdata wizard_mode_snmpdata_arrow clickable">' . html_print_image('images/darrowright.png', true, array('title' => __('Add to modules list'))) . '</div>';
	}
	$table->data[2][1] .= '<br><br><div class="wizard_mode_delete_arrow clickable">' . html_print_image('images/cross.png', true, array('title' => __('Remove from modules list'))) . '</div>';
	$table->cellstyle[2][1] = 'vertical-align: middle; text-align: center;';
	
	$table->data[2][2] = html_print_select (array (), 'module[]', 0, false, '', 0, true, true, true, '', false, 'width:300px; height: 100%;');
	$table->data[2][2] .= html_print_input_hidden('agent', $id_agent, true);
	$table->cellstyle[2][2] = 'vertical-align: top; text-align: center;';
	
	html_print_table($table);
	
	echo "<div style='text-align:right; width:" . $table->width . "'>";
	html_print_submit_button(__('Create modules'), 'create_modules_btn', false, array('class' => 'sub add'));
	echo "</div>";
	unset($table);
	
	echo "</span>";
	echo "</form>";
	echo '</div>';
}

ui_require_jquery_file ('pandora.controls');
ui_require_jquery_file ('ajaxqueue');
ui_require_jquery_file ('bgiframe');
ui_require_javascript_file ('pandora_modules');

?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */

var separator = '<?php echo $separator; ?>';

$(document).ready (function () {	
	$("#walk_form").submit(function() {
		$("#oid_loading").show ();
	});
	
	$("#snmp_version").change(function () {
		if (this.value == "3") {
			$("#snmp3_options").css("display", "");
		}
		else {
			$("#snmp3_options").css("display", "none");
		}
	});
	
	network_component_group_change_event();
	$('#network_component_group').trigger('change');
	
	$("#snmp_wizard_modes").change(function() {
		$(".wizard_mode_form").hide();
		var selected_mode = $("#snmp_wizard_modes").val();
		$(".wizard_mode_" + selected_mode).show();
		$('#form_interfaces').show();
	});
	
	$("#snmp_wizard_modes").trigger('change');
	
	<?php 
		if (!$snmpwalk || $fail) {
	?>
			$('#form_interfaces').hide();
	<?php
		}
	?>
	
	$('.wizard_mode_devices_arrow').click(function() {
		jQuery.each($("select[name='devices'] option:selected"), function (key, value) {
			var id = 'device' + separator + $(value).html() + separator + $(value).attr('value');
			var name = $(value).html() + ' (<?php echo __('Device'); ?>)';
			if (name != <?php echo "'".__('None')."'"; ?>) {
				if($("#module").find("option[value='" + id + "']").length == 0) {
					$("select[name='module[]']").append($("<option></option>").val(id).html(name));
				}
				else {
					alert('<?php echo __('Repeated'); ?>');
				}
				$("#module").find("option[value='0']").remove();
			}
		});
	});
	
	$('.wizard_mode_processes_arrow').click(function() {
		jQuery.each($("select[name='processes'] option:selected"), function (key, value) {
			var id = 'process' + separator + $(value).attr('value');
			var name = $(value).html() + ' (<?php echo __('Process'); ?>)';
			if (name != <?php echo "'".__('None')."'"; ?>) {
				if($("#module").find("option[value='" + id + "']").length == 0) {
					$("select[name='module[]']").append($("<option></option>").val(id).html(name));
				}
				else {
					alert('<?php echo __('Repeated'); ?>');
				}
				$("#module").find("option[value='0']").remove();
			}
		});
	});
	
	$('.wizard_mode_disks_arrow').click(function() {
		jQuery.each($("select[name='disks'] option:selected"), function (key, value) {
			var id = 'disk' + separator + $(value).attr('value');
			var name = $(value).html();
			if (name != <?php echo "'".__('None')."'"; ?>) {
				if($("#module").find("option[value='" + id + "']").length == 0) {
					$("select[name='module[]']").append($("<option></option>").val(id).html(name));
				}
				else {
					alert('<?php echo __('Repeated'); ?>');
				}
				$("#module").find("option[value='0']").remove();
			}
		});
	});
	
	$('.wizard_mode_temperatures_arrow').click(function() {
		jQuery.each($("select[name='temperatures'] option:selected"), function (key, value) {
			var id = 'temperature' + separator + $(value).html() + separator + $(value).attr('value');
			var name = $(value).html() + ' (<?php echo __('Temperature'); ?>)';
			if (name != <?php echo "'".__('None')."'"; ?>) {
				if($("#module").find("option[value='" + id + "']").length == 0) {
					$("select[name='module[]']").append($("<option></option>").val(id).html(name));
				}
				else {
					alert('<?php echo __('Repeated'); ?>');
				}
				$("#module").find("option[value='0']").remove();
			}
		});
	});
	
	$('.wizard_mode_snmpdata_arrow').click(function() {
		jQuery.each($("select[name='snmpdata'] option:selected"), function (key, value) {
			var id = 'snmpdata' + separator + $(value).attr('value');
			var name = $(value).html();
			if (name != <?php echo "'".__('None')."'"; ?>) {
				if($("#module").find("option[value='" + id + "']").length == 0) {
					$("select[name='module[]']").append($("<option></option>").val(id).html(name));
				}
				else {
					alert('<?php echo __('Repeated'); ?>');
				}
				$("#module").find("option[value='0']").remove();
			}
		});
	});
	
	$('.wizard_mode_delete_arrow').click(function() {
		jQuery.each($("select[name='module[]'] option:selected"), function (key, value) {
			var name = $(value).html();
			if (name != <?php echo "'".__('None')."'"; ?>) {
				$(value).remove();
			}
		});

		if($("#module option").length == 0) {
			$("select[name='module[]']").append($("<option></option>").val(0).html(<?php echo "'".__('None')."'"; ?>));
		}
	});
	
	$("#submit-create_modules_btn").click(function () {
		if ($("#module option").length == 0
			|| ($("#module option").length == 1
			&& $("#module option").eq(0).val() == 0)) {
			
			alert('<?php echo __('Modules list is empty'); ?>');
			return false;
		}
		$('#module option').map(function() {
			$(this).prop('selected', true);
		});
	});
});

/* ]]> */
</script>
