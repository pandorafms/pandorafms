<?php

// Pandora FMS - http://pandorafms.com
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

require_once ($config['homedir'].'/include/functions_users.php');

/**
 * Check if the group is in use in the Pandora DB. 
 * 
 * @param integer $idGroup The id of group.
 * 
 * @return bool Return false if the group is unused in the Pandora, else true.
 */
function groups_check_used($idGroup) {
	global $config;
	
	$return = array();
	$return['return'] = false;
	$return['tables'] = array();
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$numRows = db_get_num_rows('SELECT *
				FROM tagente WHERE id_grupo = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT *
				FROM tagente WHERE id_grupo = ' . $idGroup);
			break;
	}
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Agents'); 
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$numRows = db_get_num_rows('SELECT *
				FROM talert_actions WHERE id_group = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT *
				FROM talert_actions WHERE id_group = ' . $idGroup);
			break;
	}
	
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Alert Actions'); 
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$numRows = db_get_num_rows('SELECT * FROM talert_templates WHERE id_group = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM talert_templates WHERE id_group = ' . $idGroup);
			break;
	}
	
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Alert Templates'); 
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$numRows = db_get_num_rows('SELECT * FROM trecon_task WHERE id_group = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM trecon_task WHERE id_group = ' . $idGroup);
			break;
	}
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Recon task'); 
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$numRows = db_get_num_rows('SELECT * FROM tgraph WHERE id_group = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM tgraph WHERE id_group = ' . $idGroup);
			break;
	}
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Graphs'); 
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":	
			$numRows = db_get_num_rows('SELECT * FROM treport WHERE id_group = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM treport WHERE id_group = ' . $idGroup);
			break;
	}
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Reports'); 
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":	
			$numRows = db_get_num_rows('SELECT * FROM tlayout WHERE id_group = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM tlayout WHERE id_group = ' . $idGroup);
			break;
	}
	
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Layout visual console'); 
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$numRows = db_get_num_rows('SELECT * FROM tplanned_downtime WHERE id_group = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM tplanned_downtime WHERE id_group = ' . $idGroup);
			break;
	}
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Plannet down time'); 
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$numRows = db_get_num_rows('SELECT * FROM tgraph WHERE id_group = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM tgraph WHERE id_group = ' . $idGroup);
			break;
	}
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Graphs'); 
	}
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":	
			$numRows = db_get_num_rows('SELECT * FROM tgis_map WHERE group_id = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM tgis_map WHERE group_id = ' . $idGroup);
			break;
	}
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('GIS maps'); 
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$numRows = db_get_num_rows('SELECT * FROM tgis_map_connection WHERE group_id = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM tgis_map_connection WHERE group_id = ' . $idGroup);
			break;
	}
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('GIS connections'); 
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$numRows = db_get_num_rows('SELECT * FROM tgis_map_layer WHERE tgrupo_id_grupo = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM tgis_map_layer WHERE tgrupo_id_grupo = ' . $idGroup);
			break;
	}
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('GIS map layers'); 
	}
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":	
			$numRows = db_get_num_rows('SELECT * FROM tnetwork_map WHERE id_group = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM tnetwork_map WHERE id_group = ' . $idGroup);
			break;
	}
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Network maps'); 
	}
	
	$hookEnterprise = enterprise_include_once('include/functions_groups.php');
	if ($hookEnterprise !== ENTERPRISE_NOT_HOOK) {
		$returnEnterprise = enterprise_hook('groups_check_used_group_enterprise', array($idGroup));
		
		if ($returnEnterprise['return']) {
			$return['return'] = true;
			$return['tables'] = array_merge($return['tables'], $returnEnterprise['tables']);
		}
	}
	
	return $return;
}

/**
 * Return a array of id_group of childrens (to branches down)
 *
 * @param integer $parent The id_group parent to search the childrens.
 * @param array $groups The groups, its for optimize the querys to DB.
 */
function groups_get_childrens($parent, $groups = null, $onlyPropagate = false) {
	if (empty($groups)) {
		$groups = db_get_all_rows_in_table('tgrupo');
	}
	
	$return = array();
	
	foreach ($groups as $key => $group) {
		if ($group['id_grupo'] == 0) {
			continue;
		}
		
		if ($group['propagate'] || $onlyPropagate) {
			if ($group['parent'] == $parent) {
				$return = $return + array($group['id_grupo'] => $group) + groups_get_childrens($group['id_grupo'], $groups, $onlyPropagate);
			}
		}
		
	}
	
	return $return;
}

/**
 * Return a array of id_group of parents (to roots up).
 *
 * @param integer $parent The id_group parent to search the parent.
 * @param boolean $onlyPropagate Flag to search only parents that true to propagate.
 * @param array $groups The groups, its for optimize the querys to DB.
 */
function groups_get_parents($parent, $onlyPropagate = false, $groups = null) {
	if (empty($groups)) {
		$groups = db_get_all_rows_in_table('tgrupo');
	}
	
	$return = array();
	
	foreach ($groups as $key => $group) {
		if ($group['id_grupo'] == 0) {
			continue;
		}
		
		if (($group['id_grupo'] == $parent) && ($group['propagate'] || !$onlyPropagate)) {
			$return = $return + array($group['id_grupo'] => $group) + groups_get_parents($group['parent'], $onlyPropagate, $groups);
		}
	}
	
	return $return;
}

/**
 * Filter out groups the user doesn't have access to
 *
 * Access can be:
 * IR - Incident Read
 * IW - Incident Write
 * IM - Incident Management
 * AR - Agent Read
 * AW - Agent Write
 * LW - Alert Write
 * UM - User Management
 * DM - DB Management
 * LM - Alert Management
 * PM - Pandora Management
 *
 * @param int $id_user User id
 * @param mixed $id_group Group ID(s) to check
 * @param string $access Access privilege
 *
 * @return array Groups the user DOES have acces to (or an empty array)
 */
function groups_safe_acl ($id_user, $id_groups, $access) {
	if (!is_array ($id_groups) && check_acl ($id_user, $id_groups, $access)) {
		/* Return all the user groups if it's the group All */
		if ($id_groups == 0)
		return array_keys (users_get_groups ($id_user, $access));
		return array ($id_groups);
	}
	elseif (!is_array ($id_groups)) {
		return array ();
	}
	
	foreach ($id_groups as $group) {
		//Check ACL. If it doesn't match, remove the group
		if (!check_acl ($id_user, $group, $access)) {
			unset ($id_groups[$group]);
		}
	}
	
	return $id_groups;
}

/**
 * Get disabled field of a group
 *
 * @param int id_group Group id
 *
 * @return bool Disabled field of given group
 */
function groups_give_disabled_group ($id_group) {
	return (bool) db_get_value ('disabled', 'tgrupo', 'id_grupo', (int) $id_group);
}

/**
 * Test if the param array is all groups in db.
 *
 * @param array $id_groups
 *
 * @return bool It's true when the array is all groups in db.
 */
function groups_is_all_group($idGroups) {
	if (!is_array($idGroups))
	$arrayGroups = array($idGroups);
	else
	$arrayGroups = $idGroups;
	
	$groupsDB = db_get_all_rows_in_table ('tgrupo');
	
	$returnVar = true;
	foreach ($groupsDB as $group) {
		if (!in_array($group['id_grupo'], $arrayGroups)) {
			$returnVar = false;
			break;
		}
	}
	
	return $returnVar;
}

/**
 * Get group icon from group.
 *
 * @param int id_group Id group to get the icon
 *
 * @return string Icon path of the given group
 */
function groups_get_icon ($id_group) {
	if ($id_group == 0) {
		return 'world';
	}
	else {
		$icon = (string) db_get_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
		
		if ($icon == '') {
			$icon = 'without_group';
		}
		
		return $icon;
	}
}

/**
 * Get all groups in array with index as id_group.
 *
 * @param bool Whether to return All group or not
 *
 * @return Array with all groups selected
 */
function groups_get_all($groupWithAgents = false) {
	global $config;
	
	$sql = 'SELECT id_grupo, nombre FROM tgrupo';
	
	global $config;
	
	if ($groupWithAgents)
	$sql .= ' WHERE id_grupo IN (SELECT id_grupo FROM tagente GROUP BY id_grupo)';
	
	switch ($config['dbtype']) {
		case "mysql":
		case "postgresql":
			$sql .= ' ORDER BY nombre DESC';
			break;
		case "oracle":
			$sql .= ' ORDER BY dbms_lob.substr(nombre,4000,1) DESC';
			break;
	}
	
	$rows = db_get_all_rows_sql ($sql);
	
	if ($rows === false) {
		$rows = array();
	}
	
	$return = array();
	foreach ($rows as $row) {
		if (check_acl ($config['id_user'], $row["id_grupo"], "AR"))
		$return[$row['id_grupo']] = $row['nombre'];
	}
	
	return $return;
}

/**
 * Get all groups recursive from an initial group.
 *
 * @param int Id of the parent group
 * @param bool Whether to return All group or not
 *
 * @return Array with all result groups
 */
function groups_get_id_recursive($id_parent, $all = false) {
	$return = array();
	
	$return = array_merge($return, array($id_parent));
	
	//Check propagate
	$id = db_get_value_filter('id_grupo', 'tgrupo', array('id_grupo' => $id_parent, 'propagate' => 1));
	
	if (($id !== false) || $all) {
		$children = db_get_all_rows_filter("tgrupo", array('parent' => $id_parent, 'disabled' => 0), array('id_grupo'));
		if ($children === false) {
			$children = array();
		}
		else {
			$temp = array();
			foreach ($children as $id_children) {
				$temp = array_merge($temp, array($id_children['id_grupo']));
			}
			$children = $temp;
		}
		
		foreach ($children as $id_children) {
			$return = array_merge($return, groups_get_id_recursive($id_children, $all));
		}
	}
	
	return $return;
}

function groups_flatten_tree_groups($tree, $deep) {
	foreach ($tree as $key => $group) {
		$return[$key] = $group;
		unset($return[$key]['branch']);
		$return[$key]['deep'] = $deep;
		
		if (!empty($group['branch'])) {
			$return = $return +
				groups_flatten_tree_groups($group['branch'], $deep + 1);
		}
	}
	
	return $return;
}

/**
 * Make with a list of groups a treefied list of groups.
 *
 * @param array $groups The list of groups to create the treefield list.
 * @param integer $parent The id_group of parent actual scan branch.
 * @param integer $deep The level of profundity in the branch.
 *
 * @return array The treefield list of groups.
 */
function groups_get_groups_tree_recursive($groups, $trash = 0, $trash2 = 0) {
	$return = array();
	
	$tree = $groups;
	foreach($groups as $key => $group) {
		if ($group['id_grupo'] == 0) {
			continue;
		}
		
		// If the user has ACLs on a gruop but not in his father,
		// we consider it as a son of group "all"
		if(!in_array($group['parent'], array_keys($groups))) {
			$group['parent'] = 0;  
		}
		
		$tree[$group['parent']]['hash_branch'] = 1;
		$tree[$group['parent']]['branch'][$key] = &$tree[$key];
		
	}
	
	// Depends on the All group we give different format
	if (isset($groups[0])) {
		$tree = array($tree[0]);
	}
	else {
		$tree = $tree[0]['branch'];
	}
	
	$return = groups_flatten_tree_groups($tree, 0);
	
	return $return;
}

/**
 * Get agent status of a group.
 *
 * @param integer If of the group.
 *
 * @return int Status of the agents.
 */
function groups_get_status ($id_group = 0) {
	global $config;
	
	require_once ($config['homedir'].'/include/functions_reporting.php');
	
	$data = reporting_get_group_stats($id_group);
	
	if ($data['monitor_alerts_fired'] > 0) {
		return AGENT_STATUS_ALERT_FIRED;
	}
	elseif ($data['agent_critical'] > 0) {
		return AGENT_STATUS_CRITICAL;
	}
	elseif ($data['agent_warning'] > 0) {
		return AGENT_STATUS_WARNING;
	}
	elseif ($data['agent_unknown'] > 0) {
		return AGENT_STATUS_UNKNOWN;
	}
	else {
		return AGENT_STATUS_NORMAL;
	}
}

/**
 * This function gets the group name for a given group id
 *
 * @param int The group id
 * @param boolean $returnAllGroup Flag the return group, by default false.
 *
 * @return string The group name
 */
function groups_get_name ($id_group, $returnAllGroup = false) {
	if ($id_group > 0)
		return (string) db_get_value ('nombre', 'tgrupo', 'id_grupo', (int) $id_group);
	elseif ($returnAllGroup)
		return __("All");
}

/**
 * Return the id of a group given its name.
 *
 * @param string Name of the group.
 *
 * @return int The id of the given group.
 */
function groups_get_id ($group_name, $returnAllGroup = false) {
	return db_get_value ('id_grupo', 'tgrupo', 'nombre',  $group_name);
}

/**
 * Get all the users belonging to a group.
 *
 * @param int $id_group The group id to look for
 * @param mixed filter array
 * @param bool True if users with all permissions in the group are retrieved 
 * @param bool Is id_group an array or not #Fix
 *
 * @return array An array with all the users or an empty array
 */
function groups_get_users ($id_group, $filter = false, $return_user_all = false, $_is_array = false) {
	global $config;
	
	if (! is_array ($filter))
		$filter = array ();
	
	$filter['id_grupo'] = $id_group;
	
	$result_a = array();
	// Juanma (05/05/2014) Fix: Check for number/array id_group variable
	if ($_is_array && is_array($id_group) && !empty($id_group)) {
		$result_a = db_get_all_rows_filter ("tusuario_perfil", $filter);
	} else {
		if (!is_array($id_group) && !empty($id_group)) {
			$result_a = db_get_all_rows_filter ("tusuario_perfil", $filter);
		}
	
	}
	
	$result_b = array();
	if ($return_user_all) {
		// The users of the group All (0) will be also returned
		$filter['id_grupo'] = 0;
		$result_b = db_get_all_rows_filter ("tusuario_perfil", $filter);
	}
	
	if ($result_a == false && $result_b == false)
		$result = false;
	elseif ($result_a == false)
		$result = $result_b;
	elseif ($result_b == false)
		$result = $result_a;
	else
		$result = array_merge($result_a, $result_b);
	
	if ($result === false)
		return array ();
	
	//This removes stale users from the list. This can happen if switched to another auth scheme
	//(internal users still exist) or external auth has users removed/inactivated from the list (eg. LDAP)
	$retval = array ();
	foreach ($result as $key => $user) {
		if (!is_user ($user)) {
			unset ($result[$key]);
		}
		else {
			array_push ($retval, get_user_info ($user));
		}
	}
	
	return $retval;
}

/**
 * Returning data for a row in the groups view (Recursive function)
 *
 * @param int $id_group The group id of the row
 * @param array $group_all An array of all groups
 * @param array $group arrayy The group name and childs
 * @param array $printed_groups The printed groups list (by reference)
 *
 */
function groups_get_group_row_data($id_group, $group_all, $group, &$printed_groups) {
	global $config;
	
	$rows = array();
	$row = array();
	
	if (isset($printed_groups[$id_group])) {
		return;
	}
	
	// Store printed group to not print it again
	$printed_groups[$id_group] = 1;
	
	if ($id_group < 0) 
		return; 
	
	// Get stats for this group
	$data = reporting_get_group_stats($id_group);
	
	if ($data["total_agents"] == 0)
		return; // Skip empty groups
	
	// Calculate entire row color
	if ($data["monitor_alerts_fired"] > 0) {
		$row["status"] = "group_view_alrm";
	}
	elseif ($data["monitor_critical"] > 0) {
		$row["status"] = "group_view_crit";
	}
	elseif ($data["monitor_warning"] > 0) {
		$row["status"] = "group_view_warn";
	}
	elseif (($data["monitor_unknown"] > 0) || ($data["agents_unknown"] > 0)) {
		$row["status"] = "group_view_unk";
	}
	elseif ($data["monitor_ok"] > 0) {
		$row["status"] = "group_view_ok";
	}
	else {
		$row["status"] = "group_view_normal";
	}
	
	// Group name
	$group_cell = __('Group');
	$row[$group_cell] = $group['prefix'];
	$row[$group_cell] .= "<a href='index.php?page=agents&group=" . $id_group . "'>";
	$row[$group_cell] .= ui_print_group_icon ($id_group, true, "groups_small", '', false);
	$row[$group_cell] .= ui_print_truncate_text($group['name']);
	$row[$group_cell] .= "</a>";
	
	$row['group_name'] = ui_print_truncate_text($group['name']);
	
	if ($id_group > 0)
		$icon = (string) db_get_value ('icon', 'tgrupo', 'id_grupo', (int) $id_group);
	else
		$icon = "world";
	
	$row['group_icon'] = html_print_image("images/groups_small/" . $icon . ".png",
		true, false, true);
	
	if (!isset($html)) {
		$html = false;
	}
	
	//Update network group
	if ($html) {
		echo "<td class='group_view_data'  style='text-align: center; vertica-align: middle;'>";
		if (check_acl ($config['id_user'], $id_group, "AW")) {
			echo '<a href="index.php?sec=estado&sec2=operation/agentes/group_view&update_netgroup='.$id_group.'">' .
				html_print_image("images/target.png", true, array("border" => '0', "alt" => __('Force'))) . '</a>';
		}
		echo "</td>";
	}
	
	// Total agents
	if ($id_group != 0) {
		$data["total_agents"] = db_get_sql ("SELECT COUNT(id_agente)
			FROM tagente 
			WHERE id_grupo = $id_group AND disabled = 0");
	}
	
	// Total agents
	$row['links'][__('Agents')] = "index.php?" . 
		"page=agents&group=" . $id_group;
	$row['counts'][__('Agents')] = $data["total_agents"];
	
	$row[__('Agents')] = "<a class='link_count' href='" . $row['links'][__('Agents')] . "'>";
	$row[__('Agents')] .= $row['counts'][__('Agents')];
	$row[__('Agents')] .= "</a>";
	
	
	// Agents unknown
	$row['links'][__('Agents unknown')] = "index.php?" .
		"page=agents&group=" . $id_group . "&status=" . AGENT_STATUS_UNKNOWN;
	$row['counts'][__('Agents unknown')] = $data["agents_unknown"];
	
	$row[__('Agents unknown')] = "<a class='link_count' href='" . $row['counts'][__('Agents unknown')] . "'>";
	$row[__('Agents unknown')] .= $row['counts'][__('Agents unknown')];
	$row[__('Agents unknown')] .= "</a>";
	
	// Monitors Unknown
	$row['links'][__('Unknown')] = "index.php?" .
		"page=modules&group=" . $id_group . "&status=" . AGENT_MODULE_STATUS_UNKNOWN;
	$row['counts'][__('Unknown')] = $data["monitor_unknown"];
	
	$row[__('Unknown')] = "<a class='link_count' href='" . $row['links'][__('Unknown')] . "'>";
	$row[__('Unknown')] .= $row['counts'][__('Unknown')];
	$row[__('Unknown')] .= "</a>";
	
	// Monitors Not Init
	$row['links'][__('Not init')] = "index.php?" .
		"page=modules&group=" . $id_group . "&status=" . AGENT_MODULE_STATUS_NOT_INIT;
	$row['counts'][__('Not init')] = $data["monitor_unknown"];
	
	$row[__('Not init')] = "<a class='link_count' href='" . $row['links'][__('Not init')] . "'>";
	$row[__('Not init')] .= $row['counts'][__('Not init')];
	$row[__('Not init')] .= "</a>";
	
	// Monitors OK
	$row['links'][__('Normal')] = "index.php?" .
		"page=modules&group=" . $id_group . "&status=" . AGENT_MODULE_STATUS_NORMAL;
	$row['counts'][__('Normal')] = $data["monitor_ok"];
	
	$row[__('Normal')] = "<a class='link_count' href='" . $row['links'][__('Normal')] . "'>";
	$row[__('Normal')] .= $row['counts'][__('Normal')];
	$row[__('Normal')] .= "</a>";
	
	// Monitors Warning
	$row['links'][__('Warning')] = "index.php?" .
		"page=modules&group=" . $id_group . "&status=" . AGENT_MODULE_STATUS_WARNING;
	$row['counts'][__('Warning')] = $data["monitor_warning"];
	
	$row[__('Warning')] = "<a class='link_count' href='" . $row['links'][__('Warning')] . "'>";
	$row[__('Warning')] .= $row['counts'][__('Normal')];
	$row[__('Warning')] .= "</a>";
	
	// Monitors Critical
	$row['links'][__('Critical')] = "index.php?" .
		"page=modules&group=" . $id_group . "&status=" . AGENT_MODULE_STATUS_CRITICAL_BAD;
	$row['counts'][__('Critical')] = $data["monitor_critical"];
	
	$row[__('Critical')] = "<a class='link_count' href='" . $row['links'][__('Critical')] . "'>";
	$row[__('Critical')] .= $row['counts'][__('Critical')];
	$row[__('Critical')] .= "</a>";
	
	// Alerts fired
	$row['links'][__('Alerts fired')] = "index.php?" .
		"page=alerts&group=" . $id_group . "&status=fired";
	$row['counts'][__('Alerts fired')] = $data["monitor_alerts_fired"];
	
	$row[__('Alerts fired')] = "<a class='link_count' href='" . $row['links'][__('Alerts fired')] . "'>";
	$row[__('Alerts fired')] .= $row['counts'][__('Alerts fired')];
	$row[__('Alerts fired')] .= "</a>";
	
	$rows[$id_group] = $row;
	
	foreach($group['childs'] as $child) {
		$sub_rows = groups_get_group_row_data($child, $group_all,
			$group_all[$child], $printed_groups);
		
		if (!$html) {
			if (!empty($sub_rows))
				$rows = $rows + $sub_rows;
		}
	}
	
	return $rows;
}

function groups_get_groups_with_agent($id_user = false, $privilege = "AR", $returnAllGroup = true, $returnAllColumns = false, $id_groups = null, $keys_field = 'id_grupo') {
	$groups = users_get_groups($id_user, $privilege, $returnAllGroup, $returnAllColumns, $id_groups, $keys_field);
	
	$return = array();
	foreach ($groups as $group) {
		$data = reporting_get_group_stats($group['id_grupo']);
		
		if ($data["total_agents"] != 0) {
			$return[] = $group;
		}
	}
	
	return $return;
}

/**
 * Print a row in the groups view (Recursive function)
 *
 * @param int $id_group The group id of the row
 * @param array $group_all An array of all groups
 * @param array $group arrayy The group name and childs
 * @param array $printed_groups The printed groups list (by reference)
 *
 */
function groups_get_group_row($id_group, $group_all, $group, &$printed_groups) {
	global $config;
	
	if ($id_group < 0) 
		return;
	
	if (isset($printed_groups[$id_group])) {
		return;
	}
	
	// Store printed group to not print it again
	$printed_groups[$id_group] = 1;
	
	// Get stats for this group
	$data = reporting_get_group_stats($id_group);
	
	
	if ($data["total_agents"] == 0) {
		if (!empty($group['childs'])) {
			$group_childrens = groups_get_childrens($id_group, null, true);
			$group_childrens_agents = groups_total_agents(array_keys($group_childrens));
			
			if (empty($group_childrens_agents)) {
				return; // Skip empty groups
			}
		}
		else {
			return; // Skip empty groups
		}
	}
	
	// Calculate entire row color
	if ($data["monitor_alerts_fired"] > 0) {
		$group_class = 'group_view_alrm';
		$status_image = ui_print_status_image ('agent_alertsfired_ball.png', "", true);
	}
	elseif ($data["monitor_critical"] > 0) {
		$group_class = 'group_view_crit';
		$status_image = ui_print_status_image ('agent_critical_ball.png', "", true);
	}
	elseif ($data["monitor_warning"] > 0) {
		$group_class = 'group_view_warn';
		$status_image = ui_print_status_image ('agent_warning_ball.png', "", true);
	}
	elseif (($data["monitor_unknown"] > 0) ||  ($data["agents_unknown"] > 0)) {
		$group_class = 'group_view_unk';
		$status_image = ui_print_status_image ('agent_no_monitors_ball.png', "", true);
	}
	elseif ($data["monitor_ok"] > 0)  {
		$group_class = 'group_view_ok';
		$status_image = ui_print_status_image ('agent_ok_ball.png', "", true);
	}
	elseif ($data["agent_not_init"] > 0)  {
		$group_class = 'group_view_not_init';
		$status_image = ui_print_status_image ('agent_no_data_ball.png', "", true);
	}
	else {
		$group_class = 'group_view_normal';
		$status_image = ui_print_status_image ('agent_no_data_ball.png', "", true);
	}
	
	ob_start();
	
	echo "<tr style='height: 35px;'>";
	
	// Force
	echo "<td class='group_view_data' style='text-align: center; vertica-align: middle;'>";
	if (check_acl ($config['id_user'], $id_group, "AW")) {
		echo '<a href="index.php?sec=estado&sec2=operation/agentes/group_view&update_netgroup='.$id_group.'">' .
			html_print_image("images/target.png", true, array("border" => '0', "title" => __('Force'))) . '</a>';
	}
	echo "</td>";
	
	// Status
	// echo "<td style='text-align:center;'>" . $status_image . "</td>";
	
	// Group name
	echo "<td class='' style='font-weight: bold; font-size: 12px;'>&nbsp;&nbsp;";
	//echo $group['prefix'] . ui_print_group_icon ($id_group, true, "groups_small", 'font-size: 7.5pt');
	echo "&nbsp;<a class='' href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=$id_group'>";
	echo $group['prefix'] . ui_print_truncate_text($group['name']);
	echo "</a>";
	echo "</td>";
	
	// Total agents
	echo "<td class='group_view_data $group_class' class='group_view_data' style='font-weight: bold; font-size: 18px; text-align: center;'>";
	if ($data["total_agents"] > 0)
		echo "<a class='group_view_data $group_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
			href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=$id_group'>";
	
	//Total agent field given by function reporting_get_group_stats return the number of agents
	//of this groups and its children. It was done to print empty fathers of children groups.
	//We need to recalculate the total agents for this group here to get only the total agents
	//for this group. Of course the group All (0) is a special case.
	
	if ($id_group != 0) {
		$data["total_agents"] = db_get_sql ("SELECT COUNT(id_agente)
			FROM tagente 
			WHERE id_grupo = $id_group AND disabled = 0");
	}
	
	echo $data["total_agents"];
	echo "</a>";
	
	// Agents unknown
	if ($data["agents_unknown"] > 0) {
		echo "<td class='group_view_data group_view_data_unk $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
		echo "<a class='group_view_data group_view_data_unk $group_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
			href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=$id_group&status=" . AGENT_STATUS_UNKNOWN ."'>";
		echo $data["agents_unknown"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td class='$group_class'></td>";
	}
	
	// Agents not init
	if ($data["agent_not_init"] > 0) {
		echo "<td class='group_view_data group_view_data_unk $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
		echo "<a class='group_view_data group_view_data_unk $group_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
			href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=$id_group&status=" . AGENT_STATUS_NOT_INIT ."'>";
		echo $data["agent_not_init"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td class='$group_class'></td>";
	}
	
	// Monitors Unknown
	if ($data["monitor_unknown"] > 0) {
		echo "<td class='group_view_data group_view_data_unk $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
		echo "<a class='group_view_data group_view_data_unk $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'
			href='index.php?" .
			"sec=estado&sec2=operation/agentes/status_monitor&ag_group=$id_group&status=" . AGENT_MODULE_STATUS_UNKNOWN . "'>";
		echo $data["monitor_unknown"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td class='$group_class'></td>";
	}
	
	
	// Monitors Not Init
	if ($data["monitor_not_init"] > 0) {
		echo "<td class='group_view_data group_view_data_unk $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
		echo "<a class='group_view_data group_view_data_unk $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'
			href='index.php?" .
			"sec=estado&sec2=operation/agentes/status_monitor&ag_group=$id_group&status=" . AGENT_MODULE_STATUS_NOT_INIT . "'>";
		echo $data["monitor_not_init"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td class='$group_class'></td>";
	}
	
	
	// Monitors OK
	echo "<td class='group_view_data group_view_data_ok $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
	if ($data["monitor_ok"] > 0) {
		echo "<a class='group_view_data group_view_data_unk $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'
			href='index.php?" .
			"sec=estado&sec2=operation/agentes/status_monitor&ag_group=$id_group&status=" . AGENT_MODULE_STATUS_NORMAL . "'>";
		echo $data["monitor_ok"];
		echo "</a>";
	}
	else { 
		echo "&nbsp;";
	}
	echo "</td>";
	
	// Monitors Warning
	if ($data["monitor_warning"] > 0) {
		echo "<td class='group_view_data group_view_data_warn $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
		echo "<a class='group_view_data group_view_data_warn $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'
			href='index.php?" .
			"sec=estado&sec2=operation/agentes/status_monitor&ag_group=$id_group&status=" . AGENT_MODULE_STATUS_WARNING . "'>";
		echo $data["monitor_warning"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td class='$group_class'></td>";
	}
	
	// Monitors Critical
	if ($data["monitor_critical"] > 0) {
		echo "<td class='group_view_data group_view_data_crit $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
		echo "<a class='group_view_data group_view_data_crit $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'
			href='index.php?" .
			"sec=estado&sec2=operation/agentes/status_monitor&ag_group=$id_group&status=" . AGENT_MODULE_STATUS_CRITICAL_BAD . "'>";
		echo $data["monitor_critical"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td class='$group_class'></td>";
	}
	// Alerts fired
	if ($data["monitor_alerts_fired"] > 0) {
		echo "<td class='group_view_data group_view_data_alrm $group_class' style='font-weight: bold; font-size: 18px;  text-align: center;'>";
		echo "<a class='group_view_data group_view_data_alrm $group_class' style='font-weight: bold; font-size: 18px; text-align: center;'
			href='index.php?" .
			"sec=estado&sec2=operation/agentes/alerts_status&ag_group=$id_group&filter=fired'>";
		echo $data["monitor_alerts_fired"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td class='$group_class'></td>";
	}
	
	echo "</tr>";
	
	$row[$id_group] = ob_get_clean();
	
	
	foreach ($group['childs'] as $child) {
		if (array_key_exists($child, $group_all)) {
			$row_child = groups_get_group_row($child, $group_all, $group_all[$child], $printed_groups);
			
			if (!is_array_empty($row_child)) {
				$row = $row + $row_child;
			}
		}
	}
	
	return $row;
}

/**
 * Gets a group by id_group
 *
 * @param int $id_group The group id of the row
 *
 * @return mixed Return the group row or false
 * 
 */
function groups_get_group_by_id($id_group) {
	$result_group = db_get_row('tgrupo', 'id_grupo', $id_group);
	
	return $result_group;
}

/**
 * Create new group
 *
 * @param string Group name
 * @param array Rest of the fields of the group
 *
 * @return mixed Return group_id or false if something goes wrong
 * 
 */
function groups_create_group($group_name, $rest_values){
	
	if ($group_name == "") {
		return false;
	}
	
	$array_tmp = array('nombre' => $group_name);
	
	$values = array_merge($rest_values, $array_tmp);
	
	$check = db_get_value('nombre', 'tgrupo', 'nombre', $group_name);
	
	if (!$check) {
		$result = db_process_sql_insert('tgrupo', $values);
	}
	else {
		$result = false;
	}
	
	return $result;
}

// Get agents NOT INIT

function groups_agent_not_init ($group_array) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	$count = db_get_sql ("SELECT COUNT(*)
		FROM tagente
		WHERE disabled = 0
			AND critical_count = 0
			AND warning_count = 0
			AND unknown_count = 0
			AND (notinit_count > 0 OR total_count = 0)
			AND id_grupo IN $group_clause");
	
	return $count > 0 ? $count : 0;
}


// Get unknown agents by using the status code in modules.

function groups_agent_unknown ($group_array) {
	
	if (empty ($group_array)) {
		return 0;
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	$count = db_get_sql ("SELECT COUNT(*) FROM tagente WHERE tagente.disabled=0 AND critical_count=0 AND warning_count=0 AND unknown_count>0 AND id_grupo IN $group_clause");
	
	return $count > 0 ? $count : 0;
}

function groups_agent_total($group_array) {
	
	if (empty ($group_array)) {
		return 0;
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	$count = db_get_sql ("SELECT COUNT(*)
		FROM tagente
		WHERE tagente.disabled = 0
			AND id_grupo IN $group_clause");
	
	
	
	return $count > 0 ? $count : 0;
}

// Get ok agents by using the status code in modules.

function groups_agent_ok ($group_array) {
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	$count = db_get_sql ("SELECT COUNT(*)
		FROM tagente
		WHERE tagente.disabled = 0
			AND normal_count = total_count
			AND id_grupo IN $group_clause");
	
	
	
	return $count > 0 ? $count : 0;
}

// Get critical agents by using the status code in modules.

function groups_agent_critical ($group_array) {
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	$count = db_get_sql ("SELECT COUNT(*) FROM tagente WHERE tagente.disabled=0 AND critical_count>0 AND id_grupo IN $group_clause");
	
	return $count > 0 ? $count : 0;	
}

// Get warning agents by using the status code in modules.

function groups_agent_warning ($group_array) {
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	$count = db_get_sql ("SELECT COUNT(*) FROM tagente WHERE tagente.disabled=0 AND critical_count=0 AND warning_count>0 AND id_grupo IN $group_clause");	
	
	return $count > 0 ? $count : 0;
}


// Get monitor NOT INIT, except disabled AND async modules

function groups_monitor_not_init ($group_array) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	$count = db_get_sql ("SELECT SUM(notinit_count) FROM tagente WHERE disabled = 0 AND id_grupo IN $group_clause");	
	
	return $count > 0 ? $count : 0;
}

// Get monitor OK, except disabled and non-init

function groups_monitor_ok ($group_array) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	$count = db_get_sql ("SELECT SUM(normal_count) FROM tagente WHERE disabled = 0 AND id_grupo IN $group_clause");
	
	return $count > 0 ? $count : 0;
}

// Get monitor CRITICAL, except disabled and non-init

function groups_monitor_critical ($group_array) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	$count = db_get_sql ("SELECT SUM(critical_count) FROM tagente WHERE disabled = 0 AND id_grupo IN $group_clause");
	
	return $count > 0 ? $count : 0;
}

// Get monitor WARNING, except disabled and non-init

function groups_monitor_warning ($group_array) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	$count =  db_get_sql ("SELECT SUM(warning_count) FROM tagente WHERE disabled = 0 AND id_grupo IN $group_clause");						
	
	return $count > 0 ? $count : 0;
}

// Get monitor UNKNOWN, except disabled and non-init

function groups_monitor_unknown ($group_array) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	$count = db_get_sql ("SELECT SUM(unknown_count) FROM tagente WHERE disabled = 0 AND id_grupo IN $group_clause");
	
	return $count > 0 ? $count : 0;
}

// Get alerts defined for a given group, except disabled 

function groups_monitor_alerts ($group_array) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(talert_template_modules.id)
		FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
		WHERE tagente.id_grupo IN $group_clause AND tagente_modulo.id_agente = tagente.id_agente
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND tagente_modulo.disabled = 0 AND tagente.disabled = 0
			AND	talert_template_modules.disabled = 0 
			AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo");
}

// Get alert configured currently FIRED, except disabled 

function groups_monitor_fired_alerts ($group_array) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(talert_template_modules.id)
		FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
		WHERE tagente.id_grupo IN $group_clause AND tagente_modulo.id_agente = tagente.id_agente
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND tagente_modulo.disabled = 0 AND tagente.disabled = 0 
			AND talert_template_modules.disabled = 0 
			AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo 
			AND times_fired > 0");
}

/**
 *  Total agents in a group, (by default except disabled ones)
 *
 * @param mixed Array or (comma separated) string with groups
 * @param bool Whether to count disabled agents
 *
 * @return mixed Return group count or false if something goes wrong
 * 
 */
function groups_total_agents ($group_array, $disabled = false) {
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	$sql = "SELECT COUNT(*) FROM tagente WHERE id_grupo IN $group_clause";
	
	if (!$disabled)
		$sql .= " AND disabled = 0";
	
	return db_get_sql ($sql);
}

/**
 *  Number of disabled agents in a group
 *
 * @param mixed Array or (comma separated) string with groups
 *
 * @return mixed Return group count or false if something goes wrong
 * 
 */
function groups_agent_disabled ($group_array) {
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	$sql = "SELECT COUNT(*) FROM tagente WHERE id_grupo IN $group_clause AND disabled = 1";
	
	return db_get_sql ($sql);
}

/**
 *  Return a group row for Groups managment list
 *
 * @param mixed Group info
 * @param int total number of groups
 * @param string (ref) Concatenation of branches class with the parent classes
 *
 * @return mixed Row with html_print_table format
 * 
 */
function groups_get_group_to_list($group, $groups_count, &$symbolBranchs) {
	$tabulation = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $group['deep']);
	
	if ($group['id_grupo'] == 0) {
		$symbol = '-';
	}
	else {
		$symbol = '+';
	}
	
	$group_hash_branch = false;
	if (isset($group['hash_branch'])) {
		$group_hash_branch = $group['hash_branch'];
	}
	
	if ($group_hash_branch) {
		$data[0] = '<strong>'.$tabulation . ' ' . 
			'<a href="javascript: showBranch(' . $group['id_grupo'] .
			', ' . $group['parent'] . ');" title="' . __('Show branch children') .
			'"><span class="symbol_' . $group['id_grupo'] . ' ' . $symbolBranchs . '">' .
			$symbol . '</span> '. ui_print_truncate_text($group['nombre']) . '</a></strong>';
	}
	else {
		$data[0] = '<strong>' . $tabulation . ' ' . ui_print_truncate_text($group['nombre']) . '</strong>';
	}
	$data[1] = $group['id_grupo'];
	$data[2] = ui_print_group_icon($group['id_grupo'], true);
	$data[3] = $group['disabled'] ? __('Disabled') : __('Enabled');
	$data[4] = $group['description'];
	if ($group['id_grupo'] == 0) {
		$data[5] = '';
	}
	else {
		$data[5] = '<a href="index.php?sec=gagente&' .
			'sec2=godmode/groups/configure_group&' .
			'id_group=' . $group['id_grupo'] . '">' . html_print_image("images/config.png", true, array("alt" => __('Edit'), "title" => __('Edit'), "border" => '0'));
		//Check if there is only a group to unable delete it
		if ($groups_count > 2) {
			$data[5] .= '&nbsp;&nbsp;' .
				'<a href="index.php?sec=gagente&' .
					'sec2=godmode/groups/group_list&' .
					'id_group=' . $group['id_grupo'] . '&' .
					'delete_group=1" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">' . html_print_image("images/cross.png", true, array("alt" => __('Delete'), "border" => '0'));
		}
		else {
			$data[5] .= '&nbsp;&nbsp;' .
				ui_print_help_tip(
					__('You can not delete the last group in Pandora. A common installation must has almost one group.'), true);
		}
	}
	
	return $data;
}

/**
 * Store to be printed the subgroups rows (Recursive)
 *
 * @param mixed Group info
 * @param mixed Hash list with all the groups of 2nd or higher level
 * @param string (ref) Concatenation of branches classes to control the symbols from Javascript
 * @param int total number of groups
 * @param obj (ref) table object in html_print_table format with the stored groups
 * @param int (ref) counter of the row stored
 * @param string (ref) Concatenation of branches class with the parent classes
 * 
 */
function groups_print_group_sons($group, $sons, &$branch_classes, $groups_count, &$table, &$iterator, &$symbolBranchs) {		
	if (isset($sons[$group['id_grupo']])) {
		foreach($sons[$group['id_grupo']] as $key => $g) {
			$symbolBranchs .= ' symbol_branch_' . $g['parent'];
			
			$data = groups_get_group_to_list($g, $groups_count, $symbolBranchs);
			array_push ($table->data, $data);
			
			$branch_classes[$g['id_grupo']] = $branch_classes[$g['parent']] . ' branch_' . $g['parent'];
			$table->rowclass[$iterator] = 'parent_' . $g['parent'] . $branch_classes[$g['id_grupo']];
			
			if ($g['deep'] == 0) {
				$table->rowstyle[$iterator] = '';
			}
			else {
				if ($g['parent'] != 0) {
					$table->rowstyle[$iterator] = 'display: none;';
				}
			}
			
			$iterator++;
			
			groups_print_group_sons($g, $sons, $branch_classes, $groups_count, $table, $iterator, $symbolBranchs);
		}
	}
}

/**
 * Return an array with the groups hierarchy (Recursive)
 *
 * @param array Groups array passed by reference
 * @param mixed The id of the parent to search or false to begin the search from the first hierarchy level
 * 
 * @return array The groups reordered by its hierarchy
 */
function groups_get_tree(&$groups, $parent = false) {
	$return = array();
	
	foreach ($groups as $id => $group) {
		if ($parent === false && (!isset($group['parent']) || $group['parent'] == 0 || !in_array($group['parent'], $groups))) {
			$return[$id] = $group;
			unset($groups[$id]);
			$children = groups_get_tree($groups, $id);
			
			if (!empty($children)) {
				$return[$id]['children'] = $children;
			}
		}
		else if ($parent && isset($group['parent']) && $group['parent'] == $parent) {
			$return[$id] = $group;
			unset($groups[$id]);
			$children = groups_get_tree($groups, $id);
			
			if (!empty($children)) {
				$return[$id]['children'] = $children;
			}
		}
		else {
			continue;
		}
	}
	
	return $return;
}


function groups_get_all_hierarchy_group ($id_group, $hierarchy = array()) {
	global $config;
	
	if ($id_group == 0) {
		$hierarchy = groups_get_childrens($id_group);
	}
	else {
		$hierarchy[] = $id_group;
		$parent = db_get_value('parent','tgrupo','id_grupo',$id_group);
		
		if ($parent !== 0) {
			$propagate = db_get_value('propagate','tgrupo','id_grupo',$parent);
			
			if ($propagate == 1) {
				//$childrens_ids_parent = array($parent);
				$hierarchy[] = $parent;
				$childrens = groups_get_childrens($parent);
				if (!empty($childrens)) {
					foreach ($childrens as $child) {
						//$childrens_ids_parent[] = (int)$child['id_grupo'];
						$hierarchy[] = (int)$child['id_grupo'];
					}
				}
				
				$hierarchy = groups_get_all_hierarchy_group ($parent, $hierarchy);
			}
		}
	}
	return $hierarchy;
}
?>
