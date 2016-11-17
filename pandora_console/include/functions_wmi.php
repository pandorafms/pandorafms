<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

require_once($config['homedir'] . "/include/functions_modules.php");
require_once($config['homedir'] . "/include/functions_tags.php");

/**
 * Compose and return a WMI query
 * 
 * @param string WMI client i.e. wmic
 * @param string Administrator user of the system
 * @param string Administrator password
 * @param string host IP or host of the system
 * @param string parameter --namespace of the WMI query (if not provided, will be ignored)
 *
 * @return string WMI query
 */
function wmi_compose_query($wmi_client, $user, $password, $host, $namespace = '') {
	$wmi_command = '';
	
	if (!empty($password)) {
		$wmi_command = $wmi_client . ' -U "' . $user . '"%"' . $password . '"';
	}
	else {
		$wmi_command = $wmi_client . ' -U "' . $user . '"';
	}
	
	if (!empty($namespace)) {
		$namespace = str_replace("&quot;", "'", $namespace);
		$wmi_command .= ' --namespace="' . $namespace . '"';
	}
	
	$wmi_command .= ' //' . $host;
	
	return $wmi_command;
}


function wmi_create_wizard_modules($id_agent, $names, $wizard_mode, $values, $id_police=0, $module_id=0) {
	$results = array(ERR_GENERIC => array(), NOERR => array());
	
	if (empty($names)) {
		return array();
	}
	
	foreach($names as $name) {
		// Add query to wmi_command
		switch ($wizard_mode) {
			case 'services':
				$wmi_query = 'SELECT state FROM Win32_Service WHERE Name="' . io_safe_output($name) . '"';
				break;
			case 'processes':
				$wmi_query = 'SELECT Name FROM Win32_Process WHERE Name="' . io_safe_output($name) . '"';
				break;
			case 'disks':
				$wmi_query = 'SELECT Freespace FROM Win32_LogicalDisk WHERE DeviceID ="' . io_safe_output($name) . '"';
				break;
		}
		
		// Add the query to values
		$values['snmp_oid'] = io_safe_input($wmi_query);
		
		if($id_police != 0){
			$return = policies_create_module ($name, $id_police, $module_id, $values);
		}
		else{
			$return = modules_create_agent_module ($id_agent, $name, $values);
		}
		if($return < 0) {
			$results[ERR_GENERIC][] = $name;
		}
		else {
			$results[NOERR][] = $name;
		}
	}
	
	return $results;
}

function wmi_create_module_from_components($components, $values, $id_police=0, $module_id=0) {
	$results = array(ERR_GENERIC => array(), NOERR => array(), ERR_EXIST => array());
	
	if (empty($components)) {
		return array();
	}
	foreach ($components as $component_id) {
		$nc = db_get_row ("tnetwork_component", "id_nc", $component_id);
		
		// Compatibilize the fields between components and modules table
		if($id_police == 0){
			$nc['descripcion'] = $nc['description'];
			unset($nc['description']);
		
			$nc['nombre'] = $nc['name'];
			unset($nc['name']);
		}
		
		$nc['id_tipo_modulo'] = $nc['type'];
		unset($nc['type']);
		
		unset($nc['id_nc']);
		unset($nc['id_group']);
		if($id_police != 0){
			unset($nc['id_modulo']);
			unset($nc['wizard_level']);
		}
		// Store the passed values with the component values
		foreach ($values as $k => $v) {
			$nc[$k] = $v;
		}
		
		// Put tags in array if the component has to add them later
		if(!empty($nc['tags'])) {
			$tags = explode(',', $nc['tags']);
		}
		else {
			$tags = array();
		}
		
		unset($nc['tags']);
		
		// Check if this module exists in the agent
		if($nc['id_agente'] != ""){
			$module_name_check = db_get_value_filter('id_agente_modulo', 'tagente_modulo', array('delete_pending' => 0, 'nombre' => $nc['nombre'], 'id_agente' => $nc['id_agente']));
			}
		else{
			$module_name_check = false;
		}
		if ($module_name_check !== false) {
			$results[ERR_EXIST][] = $nc["nombre"];
		}
		else {
			if($id_police == 0){
				$id_agente_modulo = modules_create_agent_module($nc["id_agente"], $nc["nombre"], $nc);
			}
			else{
				$id_agente_modulo = policies_create_module ($nc["name"], $id_police, $module_id, $nc);	
			}

			if ($id_agente_modulo === false) {
				$results[ERR_GENERIC][] = $nc["nombre"];
			}
			else {
				if(!empty($tags)) {
					// Creating tags
					$tag_ids = array();
					foreach ($tags as $tag_name) {
						$tag_id = tags_get_id($tag_name);
						
						//If tag exists in the system we store to create it
						$tag_ids[] = $tag_id;
					}
					
					tags_insert_module_tag ($id_agente_modulo, $tag_ids);
				}
		
				$results[NOERR][] = $nc["nombre"];
			}
		}
	}
	return $results;
}
?>
