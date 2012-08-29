<?php

//Pandora FMS- http://pandorafms.com
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

include_once($config['homedir'] . "/include/functions_io.php");
include_once($config['homedir'] . "/include/functions_db.php");
include_once($config['homedir'] . "/include/functions_modules.php");

$get_modules_downtime = (bool)get_parameter('get_modules_downtime', 0);
$delete_module_from_downtime = (bool)get_parameter('delete_module_from_downtime', 0);
$add_module_into_downtime = (bool)get_parameter('add_module_into_downtime', 0);

if ($get_modules_downtime) {
	$return = array();
	$return['correct'] = 1;
	$return['in_agent'] = array();
	$return['in_downtime'] = array();
	
	$id_agent = (int)get_parameter('id_agent', 0);
	$id_downtime = (int)get_parameter('id_downtime', 0);
	$none_value = (bool)get_parameter('none_value', false);
	
	$rows = db_get_all_rows_filter('tplanned_downtime_modules',
		array('id_agent' => $id_agent, 'id_downtime' => $id_downtime));
	if (empty($rows))
		$rows = array();
	$id_modules_downtime = array();
	foreach ($rows as $row) {
		$id_modules_downtime[$row['id_agent_module']] = true;
	}
	
	$modules = db_get_all_rows_filter('tagente_modulo', array('id_agente' => $id_agent));
	if (empty($modules))
		$modules = array();
	
	foreach ($modules as $module) {
		if (empty($id_modules_downtime[$module['id_agente_modulo']])) {
			$return['in_agent'][$module['id_agente_modulo']] = io_safe_output($module['nombre']);
		}
		else {
			$return['in_downtime'][$module['id_agente_modulo']] = io_safe_output($module['nombre']);
		}
	}
	
	if ($none_value) {
		$return['in_agent'][0] = __('None');
	}
	
	echo json_encode($return);
	exit;
}

if ($delete_module_from_downtime) {
	$return = array();
	$return['correct'] = 0;
	$return['all_modules'] = 0;
	$return['id_agent'] = 0;
	
	$id_module = (int)get_parameter('id_module', 0);
	$id_downtime = (int)get_parameter('id_downtime', 0);
	
	$row = db_get_row_filter('tplanned_downtime_modules',
		array('id_agent_module' => $id_module,
			'id_downtime' => $id_downtime));
	$return['id_agent'] = $row['id_agent'];
	
	$result = db_process_sql_delete('tplanned_downtime_modules',
		array('id_downtime' => $id_downtime,
			'id_agent_module' => $id_module));
	
	if ($result) {
		$rows = db_get_all_rows_filter('tplanned_downtime_modules',
			array('id_downtime' => $id_downtime,
				'id_agent' => $row['id_agent']));
		
		if (empty($rows)) {
			db_process_sql_update('tplanned_downtime_agents',
				array('all_modules' => 1),
				array('id_agent' => $row['id_agent'],
					'id_downtime' => $id_downtime));
			
			$return['all_modules'] = 1;
			$return['id_agent'] = $row['id_agent'];
		}
		
		$return['correct'] = 1;
	}
	
	echo json_encode($return);
	exit;
}

if ($add_module_into_downtime) {
	$return = array();
	$return['correct'] = 0;
	$return['name'] = '';
	
	$id_agent = (int)get_parameter('id_agent', 0);
	$id_module = (int)get_parameter('id_module', 0);
	$id_downtime = (int)get_parameter('id_downtime', 0);
	
	$values = array();
	$values['id_agent'] = $id_agent;
	$values['id_agent_module'] = $id_module;
	$values['id_downtime'] = $id_downtime;
	
	$correct = db_process_sql_insert('tplanned_downtime_modules', $values);
	
	if ($correct) {
		db_process_sql_update('tplanned_downtime_agents',
			array('all_modules' => 0),
			array('id_agent' => $id_agent,
				'id_downtime' => $id_downtime));
		$return['correct'] = 1;
		
		$return['name'] = db_get_value('nombre', 'tagente_modulo',
			'id_agente_modulo', $id_module);
	}
	
	echo json_encode($return);
	exit;
}
?>