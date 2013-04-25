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

include_once($config['homedir'] . "/include/functions_profile.php");
include_once($config['homedir'] . '/include/functions_users.php');
include_once ($config['homedir'] . '/include/functions_groups.php');

$searchUsers = check_acl($config['id_user'], 0, "UM");

$selectUserIDUp = '';
$selectUserIDDown = '';
$selectNameUp = '';
$selectNameDown = '';
$selectEmailUp = '';
$selectEmailDown = '';
$selectLastContactUp = '';
$selectLastContactDown = '';
$selectProfileUp = '';
$selectProfileDown = '';

switch ($sortField) {
	case 'id_user':
		switch ($sort) {
			case 'up':
				$selectUserIDUp = $selected;
				$order = array('field' => 'id_user', 'order' => 'ASC');
				break;
			case 'down':
				$selectUserIDDown = $selected;
				$order = array('field' => 'id_user', 'order' => 'DESC');
				break;
		}
		break;
	case 'name':
		switch ($sort) {
			case 'up':
				$selectNameUp = $selected;
				$order = array('field' => 'fullname', 'order' => 'ASC');
				break;
			case 'down':
				$selectNameDown = $selected;
				$order = array('field' => 'fullname', 'order' => 'DESC');
				break;
		}
		break;
	case 'email':
		switch ($sort) {
			case 'up':
				$selectLastContactUp = $selected;
				$order = array('field' => 'email', 'order' => 'ASC');
				break;
			case 'down':
				$selectEmailDown = $selected;
				$order = array('field' => 'email', 'order' => 'DESC');
				break;
		}
		break;
	case 'last_contact':
		switch ($sort) {
			case 'up':
				$selectLastContactUp = $selected;
				$order = array('field' => 'last_connect', 'order' => 'ASC');
				break;
			case 'down':
				$selectLastContactDown = $selected;
				$order = array('field' => 'last_connect', 'order' => 'DESC');
				break;
		}
		break;
	case 'last_contact':
		switch ($sort) {
			case 'up':
				$selectLastContactUp = $selected;
				$order = array('field' => 'last_connect', 'order' => 'ASC');
				break;
			case 'down':
				$selectLastContactDown = $selected;
				$order = array('field' => 'last_connect', 'order' => 'DESC');
				break;
		}
		break;
	case 'profile':
		switch ($sort) {
			case 'up':
				$selectProfileUp = $selected;
				$order = array('field' => 'is_admin', 'order' => 'ASC');
				break;
			case 'down':
				$selectProfileDown = $selected;
				$order = array('field' => 'is_admin', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectUserIDUp = $selected;
		$selectUserIDDown = '';
		$selectNameUp = '';
		$selectNameDown = '';
		$selectEmailUp = '';
		$selectEmailDown = '';
		$selectLastContactUp = '';
		$selectLastContactDown = '';
		$selectProfileUp = '';
		$selectProfileDown = '';
		
		$order = array('field' => 'id_user', 'order' => 'ASC');
		break;
}

if ($searchUsers) {
	$sql = "SELECT id_user, fullname, firstname, lastname, middlename, email, last_connect, is_admin, comments FROM tusuario
		WHERE fullname LIKE '%" . $stringSearchSQL . "%' OR
			id_user LIKE '%" . $stringSearchSQL . "%' OR
			firstname LIKE '%" . $stringSearchSQL . "%' OR
			lastname LIKE '%" . $stringSearchSQL . "%' OR
			middlename LIKE '%" . $stringSearchSQL . "%' OR
			email LIKE '%" . $stringSearchSQL . "%'
		ORDER BY " . $order['field'] . " " . $order['order'] . " 
		LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	$users = db_process_sql($sql);
	
	if ($users !== false) {
		//Check ACLs
		$users_id = array();
		foreach ($users as $key => $user) {
			if (!check_acl ($config["id_user"], users_get_groups ($user["id_user"]), "UM") && $config["id_user"] != $user["id_user"]) {
				unset($users[$key]);
			}
			else {
				$users_id[] = $user["id_user"];
			}
		}
		
		if($only_count) {
			unset($users);
		}
		
		if(!$users_id) {
			$user_condition = "";
		}
		else {
			// Condition with the visible agents
			$user_condition = " AND id_user IN (\"".implode('","',$users_id)."\")";
		}
		
		$sql = "SELECT COUNT(id_user) AS count FROM tusuario
			WHERE (fullname LIKE '%" . $stringSearchSQL . "%' OR
				firstname LIKE '%" . $stringSearchSQL . "%' OR
				lastname LIKE '%" . $stringSearchSQL . "%' OR
				middlename LIKE '%" . $stringSearchSQL . "%' OR
				email LIKE '%" . $stringSearchSQL . "%')".$user_condition;
		
		$totalUsers = db_get_value_sql($sql);
	}
	else {
		$totalUsers = 0;
	}
}
?>
