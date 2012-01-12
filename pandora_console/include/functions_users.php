<?php

// Pandora FMS - http://pandorafms.com
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

/**
 * @package Include
 * @subpackage Users
 */

require_once($config['homedir'] . "/include/functions_groups.php");

/**
 * Get a list of all users in an array [username] => (info)
 *
 * @param string Field to order by (id_usuario, nombre_real or fecha_registro)
 * @param string Which info to get (defaults to nombre_real)
 *
 * @return array An array of users
 */
function users_get_info ($order = "fullname", $info = "fullname") {
	$users = get_users ($order);
	$ret = array ();
	foreach ($users as $user_id => $user_info) {
		$ret[$user_id] = $user_info[$info];
	}
	return $ret;
}

/**
 * Enable/Disable a user
 *
 * @param int user id
 * @param int new disabled value (0 when enable, 1 when disable)
 *
 * @return int sucess return
 */
function users_disable ($user_id, $new_disabled_value) {
	return db_process_sql_update('tusuario', array('disabled' => $new_disabled_value), array('id_user' => $user_id));
}

/**
 * Get all the Model groups a user has reading privileges.
 *
 * @param string User id
 * @param string The privilege to evaluate
 *
 * @return array A list of the groups the user has certain privileges.
 */
function users_get_all_model_groups () {
	$groups = db_get_all_rows_in_table ('tmodule_group');
	if($groups === false) {
		$groups = array();
	}
	$returnGroups = array();
	foreach ($groups as $group)
	$returnGroups[$group['id_mg']] = $group['name'];

	$returnGroups[0] = "Not assigned"; //Module group external to DB but it exist


	return $returnGroups;
}

/**
 * Get all the groups a user has reading privileges.
 *
 * @param string User id
 * @param string The privilege to evaluate, and it is false then no check ACL.
 * @param boolean $returnAllGroup Flag the return group, by default true.
 * @param boolean $returnAllColumns Flag to return all columns of groups.
 * @param array $id_groups The list of group to scan to bottom child. By default null.
 *
 * @return array A list of the groups the user has certain privileges.
 */
function users_get_groups ($id_user = false, $privilege = "AR", $returnAllGroup = true, $returnAllColumns = false, $id_groups = null) {
	if (empty ($id_user)) {
		global $config;
		$id_user = $config['id_user'];
	}

	if (isset($id_groups)) {
		//Get recursive id groups
		$list_id_groups = array();
		foreach ((array)$id_groups as $id_group) {
			$list_id_groups = array_merge($list_id_groups, groups_get_id_recursive($id_group));
		}

		$list_id_groups = array_unique($list_id_groups);

		$groups = db_get_all_rows_filter('tgrupo', array('id_grupo' => $list_id_groups, 'order' => 'parent, nombre'));
	}
	else {
		$groups = db_get_all_rows_in_table ('tgrupo', 'parent, nombre');
	}

	$user_groups = array ();

	if (!$groups)
	return $user_groups;

	if ($returnAllGroup) { //All group
		if ($returnAllColumns) {
			$groupall = array('id_grupo' => 0, 'nombre' => __('All'),
				'icon' => 'world', 'parent' => 0, 'disabled' => 0,
				'custom_id' => null, 'propagate' => 0); 
		}
		else {
			$groupall = array('id_grupo' => 0, 'nombre' => __("All"));
		}
		
		// Add the All group to the beginning to be always the first
		array_unshift($groups, $groupall);
	}

	foreach ($groups as $group) {
		if ($privilege === false) {
			if ($returnAllColumns) {
				$user_groups[$group['id_grupo']] = $group;
			}
			else {
				$user_groups[$group['id_grupo']] = $group['nombre'];
			}
		}
		else if (check_acl($id_user, $group["id_grupo"], $privilege)) {
			if ($returnAllColumns) {
				$user_groups[$group['id_grupo']] = $group;
			}
			else {
				$user_groups[$group['id_grupo']] = $group['nombre'];
			}
		}
	}

	return $user_groups;
}

/**
 * Get all the groups a user has reading privileges. Version for tree groups.
 *
 * @param string User id
 * @param string The privilege to evaluate
 * @param boolean $returnAllGroup Flag the return group, by default true.
 * @param boolean $returnAllColumns Flag to return all columns of groups.
 *
 * @return array A treefield list of the groups the user has certain privileges.
 */
function users_get_groups_tree($id_user = false, $privilege = "AR", $returnAllGroup = true) {
	$user_groups = users_get_groups ($id_user, $privilege, $returnAllGroup, true);

	$user_groups_tree = groups_get_groups_tree_recursive($user_groups);

	return $user_groups_tree;
}

/**
 * Get the first group of an user.
 *
 * Useful function when you need a default group for a user.
 *
 * @param string User id
 * @param string The privilege to evaluate
 * @param bool $all_group Flag to return all group, by default true;
 *
 * @return array The first group where the user has certain privileges.
 */
function users_get_first_group ($id_user = false, $privilege = "AR", $all_group = true) {
	$groups = array_keys (users_get_groups ($id_user, $privilege));

	$return = array_shift($groups);
	
	if ((!$all_group) && ($return == 0)) {
		$return = array_shift($groups);
	}
	
	return $return;
}

/**
 * Return access to a specific agent by a specific user
 *
 * @param int Agent id.
 * @param string Access mode to be checked. Default AR (Agent reading)
 * @param string User id. Current user by default
 *
 * @return bool Access to that agent (false not, true yes)
 */
function users_access_to_agent ($id_agent, $mode = "AR", $id_user = false) {
	if (empty ($id_agent))
	return false;

	if ($id_user == false) {
		global $config;
		$id_user = $config['id_user'];
	}

	$id_group = (int) db_get_value ('id_grupo', 'tagente', 'id_agente', (int) $id_agent);
	return (bool) check_acl ($id_user, $id_group, $mode);
}

/**
 * Return user by id (user name)
 *
 * @param string User id.
 *
 * @return mixed User row or false if something goes wrong
 */
function users_get_user_by_id ($id_user){
	$result_user = db_get_row('tusuario', 'id_user', $id_user);

	return $result_user;	
}

?>
