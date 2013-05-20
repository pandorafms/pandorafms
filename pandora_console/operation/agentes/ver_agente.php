<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
global $config;

require_once ('include/functions_gis.php');
require_once($config['homedir'] . '/include/functions_agents.php');
require_once($config['homedir'] . '/include/functions_groups.php');
require_once($config['homedir'] . '/include/functions_modules.php');
require_once($config['homedir'] . '/include/functions_users.php');
enterprise_include_once ('include/functions_metaconsole.php');

ui_require_javascript_file('openlayers.pandora');

enterprise_include_once ('operation/agentes/ver_agente.php');

check_login ();

if (is_ajax ()) {
	$get_agent_json = (bool) get_parameter ('get_agent_json');
	$get_agent_modules_json = (bool) get_parameter ('get_agent_modules_json');
	$get_agent_status_tooltip = (bool) get_parameter ("get_agent_status_tooltip");
	$get_agents_group_json = (bool) get_parameter ("get_agents_group_json");
	$get_agent_modules_json_for_multiple_agents = (bool) get_parameter("get_agent_modules_json_for_multiple_agents");
	$get_agent_modules_alerts_json_for_multiple_agents = (bool) get_parameter("get_agent_modules_alerts_json_for_multiple_agents");
	$get_agents_json_for_multiple_modules = (bool) get_parameter("get_agents_json_for_multiple_modules");
	$get_agent_modules_json_for_multiple_agents_id = (bool) get_parameter("get_agent_modules_json_for_multiple_agents_id");
	$get_agentmodule_status_tooltip = (bool) get_parameter ("get_agentmodule_status_tooltip");
	$get_group_status_tooltip = (bool) get_parameter ("get_group_status_tooltip");
	$get_agent_id = (bool) get_parameter ("get_agent_id");
	
	if ($get_agents_group_json) {
		$id_group = (int) get_parameter('id_group');
		$recursion = (int) get_parameter ('recursion', 0);
		
		if($id_group > 0) {
			$groups = array($id_group);
			if ($recursion) {
				$groups = array_merge($groups, groups_get_id_recursive($id_group, true));
			}
		}
		else {
			$groups_orig = users_get_groups();
			
			$groups = array_keys($groups_orig);
		}
		
		$filter = " WHERE id_grupo IN (". implode(',', $groups) .")";
		$agents = db_get_all_rows_sql("SELECT id_agente, nombre
			FROM tagente" . $filter);
		
		echo json_encode($agents);
		return;
	}
	
	if ($get_agent_json) {
		$id_agent = (int) get_parameter ('id_agent');
		
		$agent = db_get_row ('tagente', 'id_agente', $id_agent);
		
		echo json_encode ($agent);
		return;
	}
	
	if ($get_agent_modules_json_for_multiple_agents_id) {
		$idAgents = get_parameter('id_agent');
		
		$modules = db_get_all_rows_sql('SELECT nombre, id_agente_modulo
			FROM tagente_modulo WHERE id_agente IN (' . implode(',', $idAgents) . ')');
		
		$return = array();
		foreach ($modules as $module) {
			$return[$module['id_agente_modulo']] = $module['nombre'];
		}
		
		echo json_encode($return);
		return;
	}
	
	if ($get_agents_json_for_multiple_modules) {
		$nameModules = get_parameter('module_name');
		$selection_mode = get_parameter('selection_mode','common');
		
		$sql = 'SELECT DISTINCT(t1.nombre) as name
			FROM tagente t1, tagente_modulo t2
			WHERE t1.id_agente = t2.id_agente
				AND t2.nombre IN (\'' . implode('\',\'', $nameModules) . '\')';
		
		if($selection_mode == 'common') {
			$sql .= 'AND (
					SELECT count(t3.nombre)
					FROM tagente t3, tagente_modulo t4
					WHERE t3.id_agente = t4.id_agente AND t1.nombre = t3.nombre
						AND t4.nombre IN (\'' . implode('\',\'', $nameModules) . '\')) = '.count($nameModules);
		}
		
		$sql .= ' ORDER BY t1.nombre';
		
		$nameAgents = db_get_all_rows_sql($sql);
		
		if ($nameAgents == false)
			$nameAgents = array();
		
		foreach ($nameAgents as $nameAgent) {
			$names[] = $nameAgent['name'];
		}
		
		echo json_encode($names);
		return;
	}
	
	if ($get_agent_modules_alerts_json_for_multiple_agents) {
		$idAgents = get_parameter('id_agent');
		$id_template = get_parameter('template');
		
		$selection_mode = get_parameter('selection_mode','common');
		
		$sql = 'SELECT DISTINCT(nombre)
			FROM tagente_modulo t1, talert_template_modules t2
			WHERE t2.id_agent_module = t1.id_agente_modulo
				AND delete_pending = 0
				AND id_alert_template = '.$id_template.'
				AND id_agente IN (' . implode(',', $idAgents) . ')';
			
		if ($selection_mode == 'common') {
			$sql .= ' AND (
					SELECT count(nombre)
					FROM tagente_modulo t3, talert_template_modules t4
					WHERE t4.id_agent_module = t3.id_agente_modulo
						AND delete_pending = 0 AND t1.nombre = t3.nombre
						AND id_agente IN (' . implode(',', $idAgents) . ')
						AND id_alert_template = '.$id_template.') = (' . count($idAgents) . ')';
		}
		
		$sql .= ' ORDER BY t1.nombre';
		
		$nameModules = db_get_all_rows_sql($sql);
		
		if ($nameModules == false) {
			$nameModules = array();
		}
		
		$result = array();
		foreach($nameModules as $nameModule) {
			$result[] = io_safe_output($nameModule['nombre']);
		}
		
		echo json_encode($result);
		return;
	}
	
	if ($get_agent_modules_json_for_multiple_agents) {
		$idAgents = get_parameter('id_agent');
		$custom_condition = get_parameter('custom_condition', '');
		$selection_mode = get_parameter('selection_mode', 'common');
		$serialized = get_parameter('serialized', '');
		$id_server = (int)get_parameter('id_server', 0);
		$metaconsole_server_name = null;
		if ($id_server != 0) {
			$metaconsole_server_name = db_get_value('server_name',
				'tmetaconsole_setup', 'id', $id_server);
		}
		
		$all = (string)get_parameter('all', 'all');
		switch ($all) {
			default:
			case 'all':
				$enabled = '1 = 1';
				break;
			case 'enabled':
				$enabled = 'disabled = 0';
				break;
		}
		
		if ($config ['metaconsole'] == 1 and defined('METACONSOLE')) {
			$result = array();
			$nameModules = array();
			$temp = array();
			$first = true;
			$temp_element = array();
			$counter = 0;
			$first_elements = array();
			
			foreach ($idAgents as $idA) {
				if (empty($metaconsole_server_name)) {
					$row = explode ('|', $idA);
					$server_name = $row[0];
					$id_agent = $row [1];
				}
				else {
					$id_agent = $idA;
					$server_name = $metaconsole_server_name;
				}
				// New iteration
				$counter++;
				
				//Metaconsole db connection
				$connection = metaconsole_get_connection($server_name);
				if (metaconsole_load_external_db($connection) != NOERR) {
					//ui_print_error_message ("Error connecting to ".$server_name);
					continue;
				}
				
				//Get agent's modules
				$temp = agents_get_modules ($id_agent);
				
				// Keep first element to search for common modules in next iterations
				if (empty($nameModules) && $first == true) {
					$first_elements = $temp;
				}
				
				$temp = array_intersect($temp, $first_elements);
				
				// Add elements to array
				if (!empty($temp)) {
					
					// Add agent and server
					foreach ($temp as $element_key => $element_value) {
						
						//$temp_element[$element_key . '|' . $id_agent . '|' . $server_name] = $element_value;
						if (!isset($temp_element[$element_value]) && $first)
							$temp_element[$element_value] = $element_key . '|' . $id_agent . '|' . $server_name;
						else if (isset($temp_element[$element_value]))
							$temp_element[$element_value] .= ';' . $element_key . '|' . $id_agent . '|' . $server_name;
						
					}
					
				}
				// If the result array is empty then there aren't common modules 
				else if (!$first)
					unset($temp_element);
				
				
				// First iteration flag
				if (empty($nameModules) && $first == true)
					$first = false;
				
				// In last iteration we have all elements agents-servers serialized and ready to be used
				if ($counter == count($idAgents)) {
					if (!empty($temp_element))
					
						foreach ($temp_element as $temp_ele_key => $temp_ele_val) {
							$nameModules[$temp_ele_val] = $temp_ele_key;
						}
					
				}
				
				//If there's only one agent selected, get out of this loop 
				/*if (count($idAgents) <= 1) {
					//Restore db connection
					metaconsole_restore_db();
					break;
				}*/
				//$nameModules = array_intersect ($nameModules, $temp); 
				
				//Restore db connection
				metaconsole_restore_db();
			}
			
			foreach ($nameModules as $nameModule_key => $nameModule_value) {
				$result[$nameModule_key] = ui_print_truncate_text(io_safe_output($nameModule_value), 'module_medium', false, true);
			}
		}
		else {
			$sql = 'SELECT DISTINCT(nombre)
				FROM tagente_modulo t1
				WHERE ' . $enabled .
					io_safe_output($custom_condition) . '
					AND delete_pending = 0
					AND id_agente IN (' . implode(',', $idAgents) . ')';
			
			if ($selection_mode == 'common') {
				$sql .= ' AND (
							SELECT count(nombre)
							FROM tagente_modulo t2
							WHERE delete_pending = 0 AND t1.nombre = t2.nombre
								AND id_agente IN (' . implode(',', $idAgents) . ')) = (' . count($idAgents) . ')';
			}
			
			$sql .= ' ORDER BY nombre';
			
			$nameModules = db_get_all_rows_sql($sql);
			
			if ($nameModules == false) {
				$nameModules = array();
			}
			
			$result = array();
			foreach ($nameModules as $nameModule) {
				if (empty($serialized))
					$result[io_safe_output($nameModule['nombre'])] =
						ui_print_truncate_text(
							io_safe_output($nameModule['nombre']), 'module_medium', false, true);
				else
					$result[io_safe_output($nameModule['nombre']).'$*$'.implode('|', $idAgents)] = ui_print_truncate_text(io_safe_output($nameModule['nombre']), 'module_medium', false, true);
			}
		}
		
		echo json_encode($result);
		return;
	}
	
	if ($get_agent_modules_json) {
		$id_agent = (int) get_parameter ('id_agent');
		$filter = io_safe_output((string) get_parameter ('filter'));
		$fields = (string) get_parameter ('fields');
		$indexed = (bool) get_parameter ('indexed', true);
		$agentName = (string) get_parameter ('agent_name', null);
		$server_name = (string) get_parameter ('server_name', null);
		$server_id = (int) get_parameter ('server_id', 0);
		/* This will force to get local modules although metaconsole is active, by default get all modules from all nodes */
		$force_local_modules = (int) get_parameter ('force_local_modules', 0);
		
		if ($agentName != null) {
			$search = array();
			$search['name'] = io_safe_output($agentName);
		}
		else
			$search = false;
		
		if ($config ['metaconsole'] == 1 and !$force_local_modules and defined('METACONSOLE')) {
			if (enterprise_include_once ('include/functions_metaconsole.php') !== ENTERPRISE_NOT_HOOK) {
				$connection = metaconsole_get_connection($server_name);
				
				
				if ($server_id > 0) {
					$connection = metaconsole_get_connection_by_id($server_id);
				}
				
				
				if (metaconsole_load_external_db($connection) == NOERR) {
					/* Get all agents if no agent was given */
					if ($id_agent == 0)
						$id_agent = array_keys (
							agents_get_group_agents (
								array_keys (users_get_groups ()), $search, "none"));
					
					$agent_modules = agents_get_modules ($id_agent,
						($fields != '' ? explode (',', $fields) : "*"),
						($filter != '' ? $filter : false), $indexed);
				}
				// Restore db connection
				metaconsole_restore_db();
			}
		}
		else {
			/* Get all agents if no agent was given */
			if ($id_agent == 0)
				$id_agent = array_keys (agents_get_group_agents (array_keys (users_get_groups ()), $search, "none"));
			
			$agent_modules = agents_get_modules ($id_agent,
				($fields != '' ? explode (',', $fields) : "*"),
				($filter != '' ? $filter : false), $indexed);
		}
		
		foreach($agent_modules as $key => $module) {
			$agent_modules[$key]['nombre'] = io_safe_output($module['nombre']);
		}
		
		
		//Hack to translate text "any" in PHP to javascript
		//$agent_modules['any_text'] = __('Any');
		
		echo json_encode ($agent_modules);
		
		return;
	}
	
	if ($get_agent_status_tooltip) {
		$id_agent = (int) get_parameter ('id_agent');
		$metaconsole = (bool)get_parameter('metaconsole', false);
		$id_server = (int)get_parameter('id_server', 0); //Metaconsole
		
		$server = null;
		if ($metaconsole) {
			$server = db_get_row('tmetaconsole_setup', 'id', $id_server);
			
			if (metaconsole_connect($server) != NOERR) {
				return;
			}
			
			$agent = db_get_row ('tagente', 'id_agente', $id_agent);
			
			metaconsole_restore_db();
		}
		else {
			$agent = db_get_row ('tagente', 'id_agente', $id_agent);
		}
		
		
		
		
		echo '<h3>'.$agent['nombre'].'</h3>';
		echo '<strong>'.__('Main IP').':</strong> '.$agent['direccion'].'<br />';
		echo '<strong>'.__('Group').':</strong> ';
		
		$hack_metaconsole = '';
		if ($metaconsole) {
			$hack_metaconsole = '../../';
		}
		echo html_print_image($hack_metaconsole . 'images/groups_small/'.groups_get_icon ($agent['id_grupo']).'.png', true); 
		echo groups_get_name ($agent['id_grupo']).'<br />';
		
		echo '<strong>'.__('Last contact').':</strong> '.human_time_comparation($agent['ultimo_contacto']).'<br />';
		echo '<strong>'.__('Last remote contact').':</strong> '.human_time_comparation($agent['ultimo_contacto_remoto']).'<br />';
		
		
		$sql = sprintf ('SELECT tagente_modulo.descripcion,
				tagente_modulo.nombre
			FROM tagente_estado, tagente_modulo 
			WHERE tagente_modulo.id_agente = %d
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND tagente_modulo.disabled = 0 
				AND tagente_estado.estado = 1', $id_agent);
		if ($metaconsole) {
			if (metaconsole_connect($server) != NOERR) {
				return;
			}
			
			$bad_modules = db_get_all_rows_sql ($sql);
			
			metaconsole_restore_db();
		}
		else {
			$bad_modules = db_get_all_rows_sql ($sql);
		}
		
		$sql = sprintf ('SELECT COUNT(*)
			FROM tagente_modulo
			WHERE id_agente = %d
				AND disabled = 0', $id_agent);
		if ($metaconsole) {
			if (metaconsole_connect($server) != NOERR) {
				return;
			}
			
			$total_modules = db_get_sql ($sql);
			
			metaconsole_restore_db();
		}
		else {
			$total_modules = db_get_sql ($sql);
		}
		
		if ($bad_modules === false)
			$size_bad_modules = 0;
		else
			$size_bad_modules = sizeof ($bad_modules);
		
		// Modules down
		if ($size_bad_modules > 0) {
			echo '<strong>'.__('Monitors down').':</strong> '.$size_bad_modules.' / '.$total_modules;
			echo '<ul>';
			foreach ($bad_modules as $module) {
				echo '<li>';
				echo ui_print_truncate_text($module['nombre'], 'module_small');
				echo '</li>';
			}
			echo '</ul>';
		}
		
		// Alerts (if present)
		$sql = sprintf ('SELECT COUNT(talert_template_modules.id)
				FROM talert_template_modules, tagente_modulo, tagente
				WHERE tagente.id_agente = %d
					AND tagente.disabled = 0
					AND tagente.id_agente = tagente_modulo.id_agente
					AND tagente_modulo.disabled = 0
					AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
					AND talert_template_modules.times_fired > 0 ',
				$id_agent);
		if ($metaconsole) {
			if (metaconsole_connect($server) != NOERR) {
				return;
			}
			
			$alert_modules = db_get_sql ($sql);
			
			metaconsole_restore_db();
		}
		else {
			$alert_modules = db_get_sql ($sql);
		}
		
		if ($alert_modules > 0) {
			$sql = sprintf ('SELECT tagente_modulo.nombre, talert_template_modules.last_fired
				FROM talert_template_modules, tagente_modulo, tagente
				WHERE tagente.id_agente = %d
					AND tagente.disabled = 0
					AND tagente.id_agente = tagente_modulo.id_agente
					AND tagente_modulo.disabled = 0
					AND tagente_modulo.id_agente_modulo = talert_template_modules.id_agent_module
					AND talert_template_modules.times_fired > 0 ',
				$id_agent);
			if ($metaconsole) {
				if (metaconsole_connect($server) != NOERR) {
					return;
				}
				
				$alerts = db_get_all_rows_sql ($sql);
				
				metaconsole_restore_db();
			}
			else {
				$alerts = db_get_all_rows_sql ($sql);
			}
			echo '<strong>'.__('Alerts fired').':</strong>';
			echo "<ul>";
			foreach ($alerts as $alert_item) {
				echo '<li>';
				echo ui_print_truncate_text($alert_item['nombre']).' -> ';
				echo human_time_comparation($alert_item['last_fired']);
				echo '</li>';
			}
			echo '</ul>';
		}
		
		return;
	}
	
	if ($get_agentmodule_status_tooltip) {
		$id_module = (int) get_parameter ('id_module');
		$metaconsole = (bool)get_parameter('metaconsole');
		$id_server = (int)get_parameter('id_server');
		
		if ($metaconsole) {
			$server = db_get_row('tmetaconsole_setup', 'id', $id_server);
			
			if (metaconsole_connect($server) != NOERR) {
				return;
			}
		}
		
		$module = db_get_row ('tagente_modulo', 'id_agente_modulo', $id_module);
		
		echo '<h3>';
		echo html_print_image("images/brick.png", true) . '&nbsp;'; 
		echo ui_print_truncate_text($module['nombre'], 'module_small', false, true, false).'</h3>';
		echo '<strong>'.__('Type').':</strong> ';
		$agentmoduletype = modules_get_agentmodule_type ($module['id_agente_modulo']);
		echo modules_get_moduletype_name ($agentmoduletype).'&nbsp;';
		echo html_print_image("images/" . modules_get_type_icon ($agentmoduletype), true) . '<br />';
		echo '<strong>'.__('Module group').':</strong> ';
		$modulegroup =  modules_get_modulegroup_name (modules_get_agentmodule_modulegroup ($module['id_agente_modulo']));
		if ($modulegroup === false) {
			echo __('None').'<br />';
		}
		else{
			echo $modulegroup.'<br />';
		}
		echo '<strong>'.__('Agent').':</strong> ';
		echo ui_print_truncate_text(modules_get_agentmodule_agent_name($module['id_agente_modulo']), 'agent_small', false, true, false).'<br />';
		
		if ($module['id_tipo_modulo'] == 18) {
			echo '<strong>'.__('Address').':</strong> ';
			
			// Get the IP/IPs from the module description
			// Always the IP is the last part of the description (after the last space)
			$ips = explode(' ', $module['descripcion']);
			$ips = $ips[count($ips)-1];
			
			$ips = explode(',', $ips);
			if (count($ips) == 1) {
				echo $ips[0];
			}
			else {
				echo '<ul style="display:inline;">';
				foreach ($ips as $ip) {
					echo "<li>$ip</li>";
				}
				echo '</ul>';
			}
		}
		
		if ($metaconsole) {
			metaconsole_restore_db();
		}
		
		return;
	}
	
	if ($get_group_status_tooltip) {
		$id_group = (int) get_parameter ('id_group');
		$group = db_get_row ('tgrupo', 'id_grupo', $id_group);
		echo '<h3>' . html_print_image("images/groups_small/" . groups_get_icon ($group['id_grupo']) . ".png", true);
		echo ui_print_truncate_text($group['nombre'], GENERIC_SIZE_TEXT, false, true, false) . '</h3>';
		echo '<strong>'.__('Parent').':</strong> ';
		if ($group['parent'] == 0) {
			echo __('None') . '<br />';
		}
		else {
			$group_parent = db_get_row ('tgrupo', 'id_grupo', $group['parent']);
			echo html_print_image("images/groups_small/" . groups_get_icon ($group['parent']) . ".png", true); 
			echo $group_parent['nombre'] . '<br />';
		}
		echo '<strong>' . __('Sons') . ':</strong> ';
		$groups_sons = db_get_all_fields_in_table ('tgrupo', 'parent', $group['id_grupo']);
		if ($groups_sons === false) { 
			echo __('None').'<br />';
		}
		else {
			echo '<br /><br />';
			foreach($groups_sons as $group_son) {
				echo html_print_image("images/groups_small/" . groups_get_icon ($group_son['id_grupo']) . ".png", true);
				echo $group_son['nombre'].'<br />';
			}
		}
		
		return;
	}
	
	if ($get_agent_id) {
		$agent_name = (string) get_parameter ("agent_name");
		
		echo agents_get_agent_id ($agent_name);
		return;
	}
	
	return;
}

$id_agente = (int) get_parameter ("id_agente", 0);
if (empty ($id_agente)) {
	return;
}

$agent = db_get_row ('tagente', 'id_agente', $id_agente);
// get group for this id_agente
$id_grupo = $agent['id_grupo'];

$is_extra = enterprise_hook('policies_is_agent_extra_policy', array($id_agente));

if ($is_extra === ENTERPRISE_NOT_HOOK) {
	$is_extra = false;
}

if (! check_acl ($config['id_user'], $id_grupo, "AR", $id_agente) && !$is_extra) {
	db_pandora_audit("ACL Violation",
		"Trying to access (read) to agent ".agents_get_name($id_agente));
	include ("general/noaccess.php");
	return;
}

// Check for Network FLAG change request
$flag = get_parameter('flag', '');
if ($flag !== '') {
	if ($flag == 1 && check_acl ($config['id_user'], $id_grupo, "AW")) {
		$id_agent_module = get_parameter('id_agente_modulo');
		
		db_process_sql_update('tagente_modulo', array('flag' => 1), array('id_agente_modulo' => $id_agent_module));
	}
}
// Check for Network FLAG change request
$flag_agent = get_parameter('flag_agent','');
if ($flag_agent !== '') {
	if ($flag_agent == 1 && check_acl ($config['id_user'], $id_grupo, "AW")) {
		db_process_sql_update('tagente_modulo', array('flag' => 1), array('id_agente' =>$id_agente));
	}
}

if ($agent["icon_path"]) {
	$icon = gis_get_agent_icon_map($agent["id_agente"], true);
}
else {
	$icon = 'images/bricks.png';
}


///-------------Code for the tabs in the header of agent page-----------
$tab = get_parameter ("tab", "main");

/* Manage tab */
$managetab = "";
if (check_acl ($config['id_user'],$id_grupo, "AW") || $is_extra) {
	$managetab['text'] ='<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'">'
		. html_print_image("images/setup.png", true, array ("title" => __('Manage')))
		. '</a>';
	
	if ($tab == 'manage')
		$managetab['active'] = true;
	else
		$managetab['active'] = false;
		
	$managetab['godmode'] = 1;
}


/* Main tab */
$maintab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'">'
	. html_print_image("images/agent_mc.png", true, array("title" => __('Main')))
	. '</a>';
if ($tab == 'main')
	$maintab['active'] = true;
else
	$maintab['active'] = false;


/* Alert tab */
$alerttab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&tab=alert">'
	. html_print_image("images/op_alerts.png", true, array("title" => __('Alerts')))
	. '</a>';
if ($tab == 'alert')
	$alerttab['active'] = true;
else
	$alerttab['active'] = false;

/* Inventory */
$inventorytab = enterprise_hook ('inventory_tab');
if ($inventorytab == -1)
	$inventorytab = "";


/* Collection */
$collectiontab = enterprise_hook('collection_tab');
if ($collectiontab == -1)
	$collectiontab = "";


/* Policy */
$policyTab = enterprise_hook('policy_tab');
if ($policyTab == -1)
	$policyTab = "";

/* GIS tab */
$gistab="";
if ($config['activate_gis']) {
	$gistab['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=gis&id_agente='.$id_agente.'">'
		.html_print_image("images/op_gis.png", true, array( "title" => __('GIS data')))
		.'</a>';
	
	if ($tab == 'gis')
		$gistab['active'] = true;
	else 
		$gistab['active'] = false;
}


/* Incident tab */
$total_incidents = agents_get_count_incidents($id_agente);
if ($config['integria_enabled'] == 0 and $total_incidents > 0) {
	$incidenttab['text'] = '<a href="index.php?sec=gagente&amp;sec2=operation/agentes/ver_agente&tab=incident&id_agente='.$id_agente.'">' 
		. html_print_image ("images/book_edit.png", true, array ("title" =>__('Incidents')))
		. '</a>';
	
	if ($tab == 'incident')
		$incidenttab['active'] = true;
	else
		$incidenttab['active'] = false;
}


/* Url address tab */
if ($agent['url_address'] != '') {
	$urladdresstab['text'] = '<a href="index.php?sec=gagente&amp;sec2=operation/agentes/ver_agente&tab=url_address&id_agente='.$id_agente.'">' 
		. html_print_image ("images/link.png", true, array ("title" =>__('Url address')))
		. '</a>';
}
if ($tab == 'url_address')
	$urladdresstab['active'] = true;
else
	$urladdresstab['active'] = false;


/* Custom fields tab */
$custom_fields['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=custom_fields&id_agente='.$id_agente.'">'
	. html_print_image("images/custom_field.png", true, array("title" => __('Custom fields')))
	. '</a>';
if ($tab == 'custom_fields') {
	$custom_fields['active'] = true;
}
else {
	$custom_fields['active'] = false;
}


/* Graphs tab */
$graphs['text'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=graphs&id_agente='.$id_agente.'">'
	. html_print_image("images/chart.png", true, array("title" => __('Graphs')))
	. '</a>';
if ($tab == 'graphs') {
	$graphs['active'] = true;
}
else {
	$graphs['active'] = false;
}


$onheader = array('manage' => $managetab,
	'main' => $maintab, 
	'alert' => $alerttab,
	'inventory' => $inventorytab,
	'collection' => $collectiontab, 
	'gis' => $gistab,
	'custom' => $custom_fields,
	'graphs' => $graphs,
	'policy' => $policyTab);

//Added after it exists
// If the agent has incidents associated
if ($total_incidents) {
	$onheader['incident'] = $incidenttab;
}
if ($agent['url_address'] != '') {
	$onheader['url_address'] = $urladdresstab;
}

//Tabs for extensions
foreach($config['extensions'] as $extension) {
	if (isset($extension['extension_ope_tab'])) {
		
		//VMware extension is only available for VMware OS
		if ($extension['extension_ope_tab']['id'] === "vmware_manager") {
			
			//Check if OS is vmware
			$id_remote_field = db_get_value ("id_field", "tagent_custom_fields", "name", "vmware_type");
			
			$vmware_type = db_get_value_filter("description", "tagent_custom_data", array("id_field" => $id_remote_field, "id_agent" => $agent["id_agente"]));
			
			if ($vmware_type != "vm") {
				continue;
			}
			
		}
		
		//RHEV extension is only available for RHEV Virtual Machines
		if ($extension['extension_ope_tab']['id'] === "rhev_manager") {
			//Get id for remote field "rhev_type"
			$id_remote_field = db_get_value("id_field", "tagent_custom_fields", "name", "rhev_type");
			
			//Get rhev type for this agent
			$rhev_type = db_get_value_filter ("description", "tagent_custom_data", array ("id_field" => $id_remote_field, "id_agent" => $agent['id_agente']));
			
			//Check if rhev type is a vm
			if ($rhev_type != "vm") {
				continue;
			}
		}
		
		
		$image = $extension['extension_ope_tab']['icon'];
		$name = $extension['extension_ope_tab']['name'];
		$id = $extension['extension_ope_tab']['id'];
		
		$id_extension = get_parameter('id_extension', '');
		
		if ($id_extension == $id) {
			$active = true;
		}
		else {
			$active = false;
		}
		
		$url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=extension&id_agente='.$id_agente . '&id_extension=' . $id;
		
		$extension_tab = array('text' => '<a href="' . $url .'">' . html_print_image ($image, true, array ( "title" => $name)) . '</a>', 'active' => $active);
		
		$onheader = $onheader + array($id => $extension_tab);	
	}
}

$header_description = '';
switch($tab) {
	case "main":
		break;
	case "data":
		$header_description = ' - ' . __('Last data');
		break;
	case "alert":
		$header_description = ' - ' . __('Alerts');
		break;
	case "inventory":
		$header_description = ' - ' . __('Inventory');
		break;
	case "collection":
		$header_description = ' - ' . __('Collection');
		break;
	case "gis":
		$header_description = ' - ' . __('Gis');
		break;
	case "custom_fields":
		$header_description = ' - ' . __('Custom fields');
		break;
	case "graphs":
		$header_description = ' - ' . __('Graphs');
		break;
	case "policy":
		$header_description = ' - ' . __('Policy');
		break;
	case "incident":
		$header_description = ' - ' . __('Incident');
		break;
	case "url_address":
		$header_description = ' - ' . __('Url address');
		break;
}

ui_print_page_header(agents_get_name($id_agente) .
	$header_description, $icon, false, "", false, $onheader);


switch ($tab) {
	case "custom_fields":
		require ("custom_fields.php");
		break;
	case "gis":
		require ("gis_view.php");
		break;
	case "manage":
		require ("estado_generalagente.php");
		break;
	case "main":
		require ("estado_generalagente.php");
		require ("estado_monitores.php");
		require ("alerts_status.php");
		require ("status_events.php");
		break;
	case "data_view":
		require ("datos_agente.php");
		break;
	case "alert":
		require ("alerts_status.php");
		break;
	case "inventory":
		enterprise_include ("operation/agentes/agent_inventory.php");
		break;
	case "collection":
		enterprise_include ("operation/agentes/collection_view.php");
		break;
	case "policy":
		enterprise_include ("operation/agentes/policy_view.php");
		break;
	case "graphs";
		require("operation/agentes/graphs.php");
		break;
	case "incident":
		require("godmode/agentes/agent_incidents.php");
		break;
	case "url_address":
		require("operation/agentes/url_address.php");
		break;
	case "extension":
		$found = false;
		foreach($config['extensions'] as $extension) {
			if (isset($extension['extension_ope_tab'])) {
				$id = $extension['extension_ope_tab']['id'];
				$function = $extension['extension_ope_tab']['function'];
				
				$id_extension = get_parameter('id_extension', '');
				
				if ($id_extension == $id) {
					call_user_func_array($function, array());
					$found = true;
				}
			}
		}
		if (!$found) {
			ui_print_error_message ("Invalid tab specified in ".__FILE__.":".__LINE__);
		}
		break;
}
?>
