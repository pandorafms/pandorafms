<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
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
require_once ($config['homedir'].'/include/functions_users.php');

$searchAgents = check_acl($config['id_user'], 0, "AR");

$selectNameUp = '';
$selectNameDown = '';
$selectOsUp = '';
$selectOsDown = '';
$selectIntervalUp = '';
$selectIntervalDown = '';
$selectGroupUp = '';
$selectGroupDown = '';
$selectLastContactUp = '';
$selectLastContactDown = '';

switch ($sortField) {
	case 'name':
		switch ($sort) {
			case 'up':
				$selectNameUp = $selected;
				$order = array('field' => 'nombre', 'order' => 'ASC');
				break;
			case 'down':
				$selectNameDown = $selected;
				$order = array('field' => 'nombre', 'order' => 'DESC');
				break;
		}
		break;
	case 'os':
		switch ($sort) {
			case 'up':
				$selectOsUp = $selected;
				$order = array('field' => 'id_os', 'order' => 'ASC');
				break;
			case 'down':
				$selectOsDown = $selected;
				$order = array('field' => 'id_os', 'order' => 'DESC');
				break;
		}
		break;
	case 'interval':
		switch ($sort) {
			case 'up':
				$selectIntervalUp = $selected;
				$order = array('field' => 'intervalo', 'order' => 'ASC');
				break;
			case 'down':
				$selectIntervalDown = $selected;
				$order = array('field' => 'intervalo', 'order' => 'DESC');
				break;
		}
		break;
	case 'group':
		switch ($sort) {
			case 'up':
				$selectGroupUp = $selected;
				$order = array('field' => 'id_grupo', 'order' => 'ASC');
				break;
			case 'down':
				$selectGroupDown = $selected;
				$order = array('field' => 'id_grupo', 'order' => 'DESC');
				break;
		}
		break;
	case 'last_contact':
		switch ($sort) {
			case 'up':
				$selectLastContactUp = $selected;
				$order = array('field' => 'ultimo_contacto', 'order' => 'ASC');
				break;
			case 'down':
				$selectLastContactDown = $selected;
				$order = array('field' => 'ultimo_contacto', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectNameUp = $selected;
		$selectNameDown = '';
		$selectOsUp = '';
		$selectOsDown = '';
		$selectIntervalUp = '';
		$selectIntervalDown = '';
		$selectGroupUp = '';
		$selectGroupDown = '';
		$selectLastContactUp = '';
		$selectLastContactDown = '';
		$order = array('field' => 'nombre', 'order' => 'ASC');
		break;
}

$agents = false;
if ($searchAgents) {
	$userGroups = users_get_groups($config['id_user'], 'AR', false);
	$id_userGroups = array_keys($userGroups);
	
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = "
				FROM tagente AS t1
					INNER JOIN tgrupo AS t2
						ON t2.id_grupo = t1.id_grupo
				WHERE (
						1 = (
							SELECT is_admin
							FROM tusuario
							WHERE id_user = '" . $config['id_user'] . "'
						)
						OR t1.id_grupo IN (
							" . implode(',', $id_userGroups) . "
						) OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario = '" . $config['id_user'] . "'
								AND id_perfil IN (
									SELECT id_perfil
									FROM tperfil WHERE agent_view = 1
								)
							)
					)
					AND (
						t1.nombre COLLATE utf8_general_ci LIKE '%%" . $stringSearchSQL . "%%' OR
						t2.nombre COLLATE utf8_general_ci LIKE '%%" . $stringSearchSQL . "%%'
					)
			";
			break;
		case "postgresql":
		case "oracle":
			$sql = "
				FROM tagente AS t1
					INNER JOIN tgrupo AS t2
						ON t2.id_grupo = t1.id_grupo
				WHERE (
						1 = (
							SELECT is_admin
							FROM tusuario
							WHERE id_user = '" . $config['id_user'] . "'
						)
						OR t1.id_grupo IN (
							" . implode(',', $id_userGroups) . "
						) OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario = '" . $config['id_user'] . "'
								AND id_perfil IN (
									SELECT id_perfil
									FROM tperfil WHERE agent_view = 1
								)
							)
					)
					AND (
						t1.nombre LIKE '%%" . $stringSearchSQL . "%%' OR
						t2.nombre LIKE '%%" . $stringSearchSQL . "%%'
					)
			";
			break;
	}
	
	if($only_count) {
		$totalAgents = db_get_value_sql('SELECT COUNT(id_agente) AS agent_count ' . $sql);
	}
	else {
		$select = 
			"SELECT t1.id_agente, t1.ultimo_contacto, t1.nombre, t1.id_os, t1.intervalo, t1.id_grupo, t1.disabled";
		$limit = " ORDER BY " . $order['field'] . " " . $order['order'] . 
			" LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
		
		$query = $select . $sql . $limit;
		
		$agents = db_process_sql($query);
		
		if($agents !== false) {
			$totalAgents = db_get_value_sql('SELECT COUNT(id_agente) AS agent_count ' . $sql);
		}
	}
}
?>
