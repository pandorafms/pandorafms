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

enterprise_include_once('include/functions_policies.php');
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . '/include/functions_users.php');

$searchModules = check_acl($config['id_user'], 0, "AR");

$selectModuleNameUp = '';
$selectModuleNameDown = '';
$selectAgentNameUp = '';
$selectAgentNameDown = '';

switch ($sortField) {
	case 'module_name':
		switch ($sort) {
			case 'up':
				$selectModuleNameUp = $selected;
				$order = array('field' => 'module_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectModuleNameDown = $selected;
				$order = array('field' => 'module_name', 'order' => 'DESC');
				break;
		}
		break;
	case 'agent_name':
		switch ($sort) {
			case 'up':
				$selectAgentNameUp = $selected;
				$order = array('field' => 'agent_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectAgentNameDown = $selected;
				$order = array('field' => 'agent_name', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectModuleNameUp = $selected;
		$order = array('field' => 'module_name', 'order' => 'ASC');
		break;
}


$modules = false;
if ($searchModules) {
	$userGroups = users_get_groups($config['id_user'], 'AR', false);
	$id_userGroups = array_keys($userGroups);
	
	switch ($config["dbtype"]) {
		case "mysql":
			$chunk_sql = '
				FROM tagente_modulo AS t1
					INNER JOIN tagente AS t2
						ON t2.id_agente = t1.id_agente
					INNER JOIN tgrupo AS t3
						ON t3.id_grupo = t2.id_grupo
					INNER JOIN tagente_estado AS t4
						ON t4.id_agente_modulo = t1.id_agente_modulo
				WHERE (t2.id_grupo IN (' . implode(',', $id_userGroups) . ')
						OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario = "' . $config['id_user'] . '"
							AND id_perfil IN (
								SELECT id_perfil
								FROM tperfil WHERE agent_view = 1
							) 
						)
					) AND
					t1.nombre COLLATE utf8_general_ci LIKE "%' . $stringSearchSQL . '%" OR
					t3.nombre LIKE "%' . $stringSearchSQL . '%"';
			break;
		case "postgresql":
			$chunk_sql = '
				FROM tagente_modulo AS t1
					INNER JOIN tagente AS t2
						ON t2.id_agente = t1.id_agente
					INNER JOIN tgrupo AS t3
						ON t3.id_grupo = t2.id_grupo
					INNER JOIN tagente_estado AS t4
						ON t4.id_agente_modulo = t1.id_agente_modulo
				WHERE (t2.id_grupo IN (' . implode(',', $id_userGroups) . ')
						OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario = \'' . $config['id_user'] . '\'
							AND id_perfil IN (
								SELECT id_perfil
								FROM tperfil WHERE agent_view = 1
							) 
						)
					) AND
					t1.nombre COLLATE utf8_general_ci LIKE \'%' . $stringSearchSQL . '%\' OR
					t3.nombre LIKE \'%' . $stringSearchSQL . '%\'';
			break;
		case "oracle":
			$chunk_sql = '
				FROM tagente_modulo AS t1
					INNER JOIN tagente AS t2
						ON t2.id_agente = t1.id_agente
					INNER JOIN tgrupo AS t3
						ON t3.id_grupo = t2.id_grupo
					INNER JOIN tagente_estado AS t4
						ON t4.id_agente_modulo = t1.id_agente_modulo
				WHERE ' . $subquery_enterprise . ' (t2.id_grupo IN (' . implode(',', $id_userGroups) . ')
						OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario = \'' . $config['id_user'] . '\'
							AND id_perfil IN (
								SELECT id_perfil
								FROM tperfil WHERE agent_view = 1
							) 
						)
					) AND
					UPPER(t1.nombre) LIKE UPPER(\'%' . $stringSearchSQL . '%\') OR
					t3.nombre LIKE \'%' . $stringSearchSQL . '%\'';
			break;
	}
	
	$totalModules = db_get_value_sql("SELECT COUNT(t1.id_agente_modulo) AS count_modules " . $chunk_sql);

	if(!$only_count) {
		$select = "SELECT *, t1.nombre AS module_name, t2.nombre AS agent_name ";
		$limit = " ORDER BY " . $order['field'] . " " . $order['order'] . 
			" LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
		
		$modules = db_get_all_rows_sql($select . $chunk_sql . $limit);
	}
}
?>
