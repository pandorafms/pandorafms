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

global $config;

require_once ($config['homedir'] . '/include/functions_tags.php');

$action = get_parameter('action');

switch($action) {
	case 'get_tag_agents':
		$id_tag = get_parameter('id_tag');
		$id_user = get_parameter('id_user');
		$keys_prefix = get_parameter('keys_prefix');
		$only_meta = get_parameter('only_meta');
		$agent_search = get_parameter('agent_search');
		$assigned_server = get_parameter('assigned_server');
		$show_void_agents = get_parameter('show_void_agents', false);
		$no_filter_tag = get_parameter('no_filter_tag', false);
		echo wizard_get_tag_agents($id_tag, $id_user, $keys_prefix, $agent_search, $only_meta, $assigned_server, $show_void_agents, $no_filter_tag);
	break;
}

function wizard_get_tag_agents($id_tag, $id_user, $keys_prefix, $agent_search, $only_meta, $assigned_server, $show_void_agents, $no_filter_tag) {
	global $config;
	
	$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);
	$acltags = tags_get_user_module_and_tags($config['id_user']);
		
	$filter['search'] = $agent_search;
	$filter['show_void_agents'] = $show_void_agents;


	$fields = array ('tagente.id_agente', 'tagente.nombre');

	$agents = tags_get_all_user_agents ($id_tag, $id_user, $acltags, $filter, $fields, false, $strict_user);	
	
	// Add keys prefix
	if (!empty($agents)) {
		if ($keys_prefix !== "") {
			foreach($agents as $k => $v) {
				$agents_aux[$keys_prefix . $k] = $v;
				//unset($agents[$k]);
			}
		}
		$agents = $agents_aux;
	}
		
	echo json_encode ($agents);
	return;
}
?>
