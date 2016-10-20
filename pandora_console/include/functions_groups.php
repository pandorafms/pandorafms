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
function groups_get_childrens_ids($parent, $groups = null) {
	if (empty($groups)) {
		$groups = db_get_all_rows_in_table('tgrupo');
	}
	
	$return = '';
	
	foreach ($groups as $key => $group) {
		if ($group['id_grupo'] == 0) {
			continue;
		}
		
		if ($group['parent'] == $parent) {
			$return .= $group['id_grupo'] . ",";
			$propagate = db_get_value('propagate', 'tgrupo', 'id_grupo', $group['id_grupo']);
			if ($propagate) {
				$return .= groups_get_childrens_ids($group['id_grupo']);
			}
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
		
		if (($group['id_grupo'] == $parent)
			&& ($group['propagate'] || !$onlyPropagate)) {
			
			$return = $return +
				array($group['id_grupo'] => $group) +
				groups_get_parents($group['parent'], $onlyPropagate, $groups);
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
	$sql .= ' WHERE id_grupo IN (
		SELECT id_grupo
		FROM tagente
		GROUP BY id_grupo)';
	
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
function groups_get_status ($id_group = 0, $strict_user = false) {
	global $config;
	
	require_once ($config['homedir'].'/include/functions_reporting.php');
	
	if ($strict_user) {
		$acltags = tags_get_user_module_and_tags ($config['id_user'], 'AR', $strict_user);
		$group_status = group_get_data ($config['id_user'], $strict_user, $acltags, false, 'group');
		$data['monitor_alerts_fired'] = $groups_status['_monitors_alerts_fired_'];
		$data['agent_critical'] = $groups_status['_agents_critical_'];
		$data['agent_warning'] = $groups_status['_agents_warning_'];
		$data['agent_unknown'] = $groups_status['_agents_unknown_'];
		
	} else {
		$data = reporting_get_group_stats($id_group);
	}
	
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
function groups_create_group($group_name, $rest_values) {
	
	if ($group_name == "") {
		return false;
	}
	
	$array_tmp = array('nombre' => $group_name);
	
	$values = array_merge($rest_values, $array_tmp);
	
	if (!isset($values['propagate'])) {
		$values['propagate'] = 0;
	}
	if (!isset($values['disabled'])) {
		$values['disabled'] = 0;
	}
	
	$check = db_get_value('nombre', 'tgrupo', 'nombre', $group_name);
	
	if (!$check) {
		$result = db_process_sql_insert('tgrupo', $values);
	}
	else {
		$result = false;
	}
	
	return $result;
}

/**
 * Get the number of the agents that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'status': (mixed) Agent status. Single or grouped into an array. e.g.: AGENT_STATUS_CRITICAL.
 * -'name': (string) Agent name. e.g.: "agent_1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of agents.
 * 
 */
function groups_get_agents_counter ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	
	if (empty($group)) {
		return 0;
	}
	else if (is_array($group)) {
		$groups = $group;
	}
	else {
		$groups = array($group);
	}
	
	$group_str = implode (",", $groups);
	$groups_clause = "AND ta.id_grupo IN ($group_str)";

	$tags_clause = "";
	if ($strict_user && !empty($groups_and_tags)) {
		foreach ($groups as $group_id) {
			if (isset($groups_and_tags[$group_id]) && !empty($groups_and_tags[$group_id])) {
				$tags_str = $groups_and_tags[$group_id];
				$tags_clause .= " AND (ta.grupo <> $group_id
									OR (ta.grupo = $group_id
										AND tam.id_agente_modulo NOT IN (SELECT id_agente_modulo
																		FROM ttag_module
																		WHERE id_tag NOT IN ($tags_str) )))";
			}
		}
	}
	
	$agent_name_filter = "";
	$agent_status = AGENT_STATUS_ALL;
	if (!empty($agent_filter)) {
		// Name
		if (isset($agent_filter["name"]) && !empty($agent_filter["name"])) {
			$agent_name_filter = "AND ta.nombre LIKE '%" . $agent_filter["name"] . "%'";
		}
		// Status
		if (isset($agent_filter["status"])) {
			if (is_array($agent_filter["status"]))
				$agent_status = array_unique($agent_filter["status"]);
			else
				$agent_status = $agent_filter["status"];
		}
	}
	
	$module_name_filter = "";
	$module_status_filter = "";
	$module_status_array = array();
	if (!empty($module_filter)) {
		// IMPORTANT: The module filters will force the realtime search
		$realtime = true;
		
		// Name
		if (isset($module_filter["name"]) && !empty($module_filter["name"])) {
			$module_name_filter = "AND tam.nombre LIKE '%" . $module_filter["name"] . "%'";
		}
		// Status
		if (isset($module_filter["status"])) {
			$module_status = $module_filter["status"];
			if (is_array($module_status))
				$module_status = array_unique($module_status);
			else
				$module_status = array($module_status);
			
			foreach ($module_status as $status) {
				switch ($status) {
					case AGENT_MODULE_STATUS_ALL:
						$module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
						$module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
						$module_status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
						$module_status_array[] = AGENT_MODULE_STATUS_WARNING;
						$module_status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
						$module_status_array[] = AGENT_MODULE_STATUS_NO_DATA;
						$module_status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
						$module_status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
						$module_status_array[] = AGENT_MODULE_STATUS_NORMAL;
						break;
					case AGENT_MODULE_STATUS_CRITICAL_ALERT:
					case AGENT_MODULE_STATUS_CRITICAL_BAD:
						$module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
						$module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
						break;
					case AGENT_MODULE_STATUS_WARNING_ALERT:
					case AGENT_MODULE_STATUS_WARNING:
						$module_status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
						$module_status_array[] = AGENT_MODULE_STATUS_WARNING;
						break;
					case AGENT_MODULE_STATUS_UNKNOWN:
						$module_status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
						break;
					case AGENT_MODULE_STATUS_NO_DATA:
					case AGENT_MODULE_STATUS_NOT_INIT:
						$module_status_array[] = AGENT_MODULE_STATUS_NO_DATA;
						$module_status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
						break;
					case AGENT_MODULE_STATUS_NORMAL_ALERT:
					case AGENT_MODULE_STATUS_NORMAL:
						$module_status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
						$module_status_array[] = AGENT_MODULE_STATUS_NORMAL;
						break;
				}
			}
			if (!empty($module_status_array)) {
				$module_status_array = array_unique($module_status_array);
				$status_str = implode(",", $module_status_array);
				
				$module_status_filter = "INNER JOIN tagente_estado tae
											ON tam.id_agente_modulo = tae.id_agente_modulo
												AND tae.estado IN ($status_str)";
			}
		}
	}
	
	$count = 0;
	// Realtime
	if ($realtime) {
		$sql = "SELECT DISTINCT ta.id_agente
				FROM tagente ta
				INNER JOIN tagente_modulo tam
					ON ta.id_agente = tam.id_agente
						AND tam.disabled = 0
						$module_name_filter
				$module_status_filter
				WHERE ta.disabled = 0
					$agent_name_filter
					$groups_clause
					$tags_clause";
		$agents = db_get_all_rows_sql($sql);

		if ($agents === false)
			return $count;

		if ($agent_status == AGENT_STATUS_ALL)
			return count($agents);
		
		foreach ($agents as $agent) {
			$agent_filter["id"] = $agent["id_agente"];
			
			$total = 0;
			$critical = 0;
			$warning = 0;
			$unknown = 0;
			$not_init = 0;
			$normal = 0;
			if (empty($module_status_array)) {
				$total = (int) groups_get_total_monitors ($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
				$critical = (int) groups_get_critical_monitors ($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
				$warning = (int) groups_get_warning_monitors ($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
				$unknown = (int) groups_get_unknown_monitors ($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
				$not_init = (int) groups_get_not_init_monitors ($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
				$normal = (int) groups_get_normal_monitors ($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
			}
			else {
				foreach ($module_status_array as $status) {
					switch ($status) {
						case AGENT_MODULE_STATUS_CRITICAL_ALERT:
						case AGENT_MODULE_STATUS_CRITICAL_BAD:
							$critical = (int) groups_get_critical_monitors ($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
							break;
						case AGENT_MODULE_STATUS_WARNING_ALERT:
						case AGENT_MODULE_STATUS_WARNING:
							$warning = (int) groups_get_warning_monitors ($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
							break;
						case AGENT_MODULE_STATUS_UNKNOWN:
							$unknown = (int) groups_get_unknown_monitors ($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
							break;
						case AGENT_MODULE_STATUS_NO_DATA:
						case AGENT_MODULE_STATUS_NOT_INIT:
							$not_init = (int) groups_get_not_init_monitors ($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
							break;
						case AGENT_MODULE_STATUS_NORMAL_ALERT:
						case AGENT_MODULE_STATUS_NORMAL:
							$normal = (int) groups_get_normal_monitors ($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
							break;
					}
				}
				
				$total = $critical + $warning + $unknown + $not_init + $normal;
			}
			
			if (!is_array($agent_status)) {
				switch ($agent_status) {
					case AGENT_STATUS_CRITICAL:
						if ($critical > 0)
							$count ++;
						break;
					case AGENT_STATUS_WARNING:
						if (($total > 0) && ($critical == 0) && ($warning > 0))
							$count ++;
						break;
					case AGENT_STATUS_UNKNOWN:
						if ($critical == 0 && $warning == 0 && $unknown > 0)
							$count ++;
						break;
					case AGENT_STATUS_NOT_INIT:
						if ($total == 0 || $total == $not_init)
							$count ++;
						break;
					case AGENT_STATUS_NORMAL:
						if ($critical == 0 && $warning == 0 && $unknown == 0 && $normal > 0)
							$count ++;
						break;
					default:
						// The status doesn't exist
						return 0;
				}
			}
			else {
				if (array_search(AGENT_STATUS_CRITICAL, $agent_status) !== false) {
					if ($critical > 0)
						$count ++;
				}
				else if (array_search(AGENT_STATUS_WARNING, $agent_status) !== false) {
					if ($total > 0 && $critical = 0 && $warning > 0)
						$count ++;
				}
				else if (array_search(AGENT_STATUS_UNKNOWN, $agent_status) !== false) {
					if ($critical == 0 && $warning == 0 && $unknown > 0)
						$count ++;
				}
				else if (array_search(AGENT_STATUS_NOT_INIT, $agent_status) !== false) {
					if ($total == 0 || $total == $not_init)
						$count ++;
				}
				else if (array_search(AGENT_STATUS_NORMAL, $agent_status) !== false) {
					if ($critical == 0 && $warning == 0 && $unknown == 0 && $normal > 0)
						$count ++;
				}
				// Invalid status
				else {
					return 0;
				}
			}
		}
	}
	// Server processed
	else {
		$status_filter = "";
		// Transform the element into a one element array
		if (!is_array($agent_status))
			$agent_status = array($agent_status);
		
		// Support for multiple status. It counts the agents for each status and sum the result
		foreach ($agent_status as $status) {
			switch ($agent_status) {
				case AGENT_STATUS_ALL:
					$status_filter = "";
					break;
				case AGENT_STATUS_CRITICAL:
					$status_filter = "AND ta.critical_count > 0";
					break;
				case AGENT_STATUS_WARNING:
					$status_filter = "AND ta.total_count > 0
									AND ta.critical_count = 0
									AND ta.warning_count > 0";
					break;
				case AGENT_STATUS_UNKNOWN:
					$status_filter = "AND ta.critical_count = 0
									AND ta.warning_count = 0
									AND ta.unknown_count > 0";
					break;
				case AGENT_STATUS_NOT_INIT:
					$status_filter = "AND (ta.total_count = 0
										OR ta.total_count = ta.notinit_count)";
					break;
				case AGENT_STATUS_NORMAL:
					$status_filter = "AND ta.critical_count = 0
									AND ta.warning_count = 0
									AND ta.unknown_count = 0
									AND ta.normal_count > 0";
					break;
				default:
					// The type doesn't exist
					return 0;
			}
			
			$sql = "SELECT COUNT(DISTINCT ta.id_agente) 
					FROM tagente ta
					WHERE ta.disabled = 0
						$agent_name_filter
						$status_filter
						$groups_clause";
			
			$res = db_get_sql($sql);
			if ($res !== false)
				$count += $res;
		}
	}

	return $count;
}

/**
 * Get the number of the agents that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of agents.
 * 
 */
function groups_get_total_agents ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the agent status filter
	$agent_filter["status"] = AGENT_STATUS_ALL;
	return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

/**
 * Get the number of the normal agents that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of agents.
 * 
 */
function groups_get_normal_agents ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the agent status filter
	$agent_filter["status"] = AGENT_STATUS_NORMAL;
	return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

/**
 * Get the number of the critical agents that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of agents.
 * 
 */
function groups_get_critical_agents ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the agent status filter
	$agent_filter["status"] = AGENT_STATUS_CRITICAL;
	return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

/**
 * Get the number of the warning agents that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of agents.
 * 
 */
function groups_get_warning_agents ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the agent status filter
	$agent_filter["status"] = AGENT_STATUS_WARNING;
	return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

/**
 * Get the number of the unknown agents that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of agents.
 * 
 */
function groups_get_unknown_agents ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the agent status filter
	$agent_filter["status"] = AGENT_STATUS_UNKNOWN;
	return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

/**
 * Get the number of the not init agents that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of agents.
 * 
 */
function groups_get_not_init_agents ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the agent status filter
	$agent_filter["status"] = AGENT_STATUS_NOT_INIT;
	return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

/**
 * Get the number of the monitors that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * -'id': (mixed) Agent id. e.g.: "1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of monitors.
 * 
 */
function groups_get_monitors_counter ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	
	if (empty($group)) {
		return 0;
	}
	else if (is_array($group)) {
		$groups = $group;
	}
	else {
		$groups = array($group);
	}
	
	if ($strict_user) {
		$realtime = true;
	}
	
	$group_str = implode (",", $groups);
	$groups_clause = "AND ta.id_grupo IN ($group_str)";
	
	$tags_clause = "";
	if ($strict_user && !empty($groups_and_tags)) {
		foreach ($groups as $group_id) {
			if (isset($groups_and_tags[$group_id]) && !empty($groups_and_tags[$group_id])) {
				$tags_str = $groups_and_tags[$group_id];
				$tags_clause .= " AND (ta.grupo <> $group_id
									OR (ta.grupo = $group_id
										AND tam.id_agente_modulo NOT IN (SELECT id_agente_modulo
																		FROM ttag_module
																		WHERE id_tag NOT IN ($tags_str) )))";
			}
		}
	}
	
	$agent_name_filter = "";
	$agents_clause = "";
	if (!empty($agent_filter)) {
		// Name
		if (isset($agent_filter["name"]) && !empty($agent_filter["name"])) {
			$agent_name_filter = "AND ta.nombre LIKE '%" . $agent_filter["name"] . "%'";
		}
		// ID
		if (isset($agent_filter["id"])) {
			if (is_array($agent_filter["id"]))
				$agents = array_unique($agent_filter["id"]);
			else
				$agents = array($agent_filter["id"]);
			$agents_str = implode (",", $agents);
			$agents_clause = "AND ta.id_agente IN ($agents_str)";
		}
	}
	

	$module_name_filter = "";
	$module_status_array = "";
	$modules_clause = "";
	if (!empty($module_filter)) {
		
		// Name
		if (isset($module_filter["name"]) && !empty($module_filter["name"])) {
			// IMPORTANT: The module filters will force the realtime search
			$realtime = true;
			
			$module_name_filter = "AND tam.nombre LIKE '%" . $module_filter["name"] . "%'";
		}
		// Status
		if (isset($module_filter["status"])) {
			if (is_array($module_filter["status"]))
				$module_status = array_unique($module_filter["status"]);
			else
				$module_status = array($module_filter["status"]);
			
			$status_array = "";
			foreach ($module_status as $status) {
				switch ($status) {
					case AGENT_MODULE_STATUS_ALL:
						$status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
						$status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
						$status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
						$status_array[] = AGENT_MODULE_STATUS_WARNING;
						$status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
						$status_array[] = AGENT_MODULE_STATUS_NO_DATA;
						$status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
						$status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
						$status_array[] = AGENT_MODULE_STATUS_NORMAL;
						break;
					case AGENT_MODULE_STATUS_CRITICAL_ALERT:
					case AGENT_MODULE_STATUS_CRITICAL_BAD:
						$status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
						$status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
						break;
					case AGENT_MODULE_STATUS_WARNING_ALERT:
					case AGENT_MODULE_STATUS_WARNING:
						$status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
						$status_array[] = AGENT_MODULE_STATUS_WARNING;
						break;
					case AGENT_MODULE_STATUS_UNKNOWN:
						$status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
						break;
					case AGENT_MODULE_STATUS_NO_DATA:
					case AGENT_MODULE_STATUS_NOT_INIT:
						$status_array[] = AGENT_MODULE_STATUS_NO_DATA;
						$status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
						break;
					case AGENT_MODULE_STATUS_NORMAL_ALERT:
					case AGENT_MODULE_STATUS_NORMAL:
						$status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
						$status_array[] = AGENT_MODULE_STATUS_NORMAL;
						break;
					default:
						// The status doesn't exist
						return 0;
				}
			}
			if (!empty($status_array)) {
				$status_array = array_unique($status_array);
				$status_str = implode(",", $status_array);
				
				$modules_clause = "AND tae.estado IN ($status_str)";
			}
		}
	}
	
	if ($realtime) {
		$sql = "SELECT COUNT(DISTINCT tam.id_agente_modulo)
				FROM tagente_modulo tam
				INNER JOIN tagente_estado tae
					ON tam.id_agente_modulo = tae.id_agente_modulo
						$modules_clause
				INNER JOIN tagente ta
					ON tam.id_agente = ta.id_agente
						AND ta.disabled = 0
						$agent_name_filter
						$agents_clause
						$groups_clause
				WHERE tam.disabled = 0
					$module_name_filter
					$tags_clause";
	}
	else {
		$status_columns_array = array();
		foreach ($module_status_array as $status) {
			switch ($status) {
				case AGENT_MODULE_STATUS_CRITICAL_ALERT:
				case AGENT_MODULE_STATUS_CRITICAL_BAD:
					$status_columns_array[] = 'ta.critical_count';
					break;
				case AGENT_MODULE_STATUS_WARNING_ALERT:
				case AGENT_MODULE_STATUS_WARNING:
					$status_columns_array[] = 'ta.warning_count';
					break;
				case AGENT_MODULE_STATUS_UNKNOWN:
					$status_columns_array[] = 'ta.unknown_count';
					break;
				case AGENT_MODULE_STATUS_NO_DATA:
				case AGENT_MODULE_STATUS_NOT_INIT:
					$status_columns_array[] = 'ta.notinit_count';
					break;
				case AGENT_MODULE_STATUS_NORMAL_ALERT:
				case AGENT_MODULE_STATUS_NORMAL:
					$status_columns_array[] = 'ta.normal_count';
					break;
				default:
					// The type doesn't exist
					return 0;
			}
		}
		if (empty($status_columns_array))
			return 0;
		
		$status_columns_str = implode(",", $status_columns_array);
		
		$sql = "SELECT SUM($status_columns_str)
				FROM tagente ta
				WHERE ta.disabled = 0
					$agent_name_filter
					$agents_clause
					$groups_clause
					$tags_clause";
	}
			
	$count = (int) db_get_sql ($sql);

	return $count;	
}

/**
 * Get the number of the monitors that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * -'id': (int/array) Agent id. e.g.: "1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of monitors.
 * 
 */
function groups_get_total_monitors ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the module status filter
	$module_filter["status"] = AGENT_MODULE_STATUS_ALL;
	return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

/**
 * Get the number of the normal monitors that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * -'id': (int/array) Agent id. e.g.: "1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of monitors.
 * 
 */
function groups_get_normal_monitors ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the module status filter
	$module_filter["status"] = AGENT_MODULE_STATUS_NORMAL;
	return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

/**
 * Get the number of the critical monitors that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * -'id': (int/array) Agent id. e.g.: "1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of monitors.
 * 
 */
function groups_get_critical_monitors ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the module status filter
	$module_filter["status"] = AGENT_MODULE_STATUS_CRITICAL_BAD;
	return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

/**
 * Get the number of the warning monitors that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * -'id': (int/array) Agent id. e.g.: "1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of monitors.
 * 
 */
function groups_get_warning_monitors ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the module status filter
	$module_filter["status"] = AGENT_MODULE_STATUS_WARNING;
	return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

/**
 * Get the number of the unknown monitors that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * -'id': (int/array) Agent id. e.g.: "1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of monitors.
 * 
 */
function groups_get_unknown_monitors ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the module status filter
	$module_filter["status"] = AGENT_MODULE_STATUS_UNKNOWN;
	return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

/**
 * Get the number of the not init monitors that pass the filters.
 *
 * @param mixed $group Id in integer or a set of ids into an array.
 * @param array $agent_filter Filter of the agents.
 * This filter support the following fields:
 * -'name': (string) Agent name. e.g.: "agent_1".
 * -'id': (int/array) Agent id. e.g.: "1".
 * @param array $module_filter Filter of the modules.
 * This filter support the following fields:
 * -'name': (string) Module name. e.g.: "module_1".
 * @param bool $strict_user If the user has enabled the strict ACL mode or not.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param bool $realtime Search realtime values or the values processed by the server.
 *
 * @return int Number of monitors.
 * 
 */
function groups_get_not_init_monitors ($group, $agent_filter = array(), $module_filter = array(), $strict_user = false, $groups_and_tags = false, $realtime = false) {
	// Always modify the module status filter
	$module_filter["status"] = AGENT_MODULE_STATUS_NOT_INIT;
	return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}

// Get alerts defined for a given group, except disabled 

function groups_monitor_alerts ($group_array, $strict_user = false, $id_group_strict = false) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	if ($strict_user) {
		$sql = "SELECT COUNT(talert_template_modules.id)
			FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
			WHERE tagente.id_grupo = $id_group_strict AND tagente_modulo.id_agente = tagente.id_agente
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND tagente_modulo.disabled = 0 AND tagente.disabled = 0
				AND	talert_template_modules.disabled = 0 
				AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo";
		$count = db_get_sql ($sql);
		return $count;
	} else {
		//TODO REVIEW ORACLE AND POSTGRES
		return db_get_sql ("SELECT COUNT(talert_template_modules.id)
			FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
			WHERE tagente.id_grupo IN $group_clause AND tagente_modulo.id_agente = tagente.id_agente
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND tagente_modulo.disabled = 0 AND tagente.disabled = 0
				AND	talert_template_modules.disabled = 0 
				AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo");
	}
}

// Get alert configured currently FIRED, except disabled 

function groups_monitor_fired_alerts ($group_array, $strict_user = false, $id_group_strict = false) {
	
	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	}
	else if (!is_array ($group_array)) {
		$group_array = array($group_array);
	}
	
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	if ($strict_user) {
		$sql = "SELECT COUNT(talert_template_modules.id)
		FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
		WHERE tagente.id_grupo = $id_group_strict AND tagente_modulo.id_agente = tagente.id_agente
			AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			AND tagente_modulo.disabled = 0 AND tagente.disabled = 0 
			AND talert_template_modules.disabled = 0 
			AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo 
			AND times_fired > 0 ";

		$count = db_get_sql ($sql);
		return $count;
	} else {
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
	else if (!is_array ($group_array)) {
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

function group_get_data ($id_user = false, $user_strict = false, $acltags, $returnAllGroup = false, $mode = 'group', $agent_filter = array(), $module_filter = array()) {
	global $config;
	if ($id_user == false) {
		$id_user = $config['id_user'];
	}
	
	$user_groups = array();
	$user_tags = array();
	$groups_without_tags = array();
	foreach ($acltags as $group => $tags) {
		if ($user_strict) { //Remove groups with tags
			if ($tags == '') {
				$groups_without_tags[$group] = $group;
			}
		}
		$user_groups[$group] = groups_get_name($group);
		if ($tags != '') {
			$tags_group = explode(',', $tags);
	
			foreach ($tags_group as $tag) {
				$user_tags[$tag] = tags_get_name($tag);
			}
		}
	}
	
	if ($user_strict) {
		$user_groups_ids = implode(',', array_keys($groups_without_tags));
	}
	else {
		$user_groups_ids = implode(',', array_keys($acltags));
	}
	
	if (!empty($user_groups_ids)) {
		switch ($config["dbtype"]) {
			case "mysql":
				$list_groups = db_get_all_rows_sql("
					SELECT *
					FROM tgrupo
					WHERE id_grupo IN (" . $user_groups_ids . ")
					ORDER BY nombre COLLATE utf8_general_ci ASC");
				break;
			case "postgresql":
				$list_groups = db_get_all_rows_sql("
					SELECT *
					FROM tgrupo
					WHERE id_grupo IN (" . $user_groups_ids . ")
					ORDER BY nombre ASC");
				break;
			case "oracle":
				$list_groups = db_get_all_rows_sql("
					SELECT *
					FROM tgrupo
					WHERE id_grupo IN (" . $user_groups_ids . ")
					ORDER BY nombre ASC");
				break;
		}
	}
	
	$list = array();
	
	if ($list_groups == false) {
		$list_groups = array();
	}
	
	if ($returnAllGroup) {
		$i = 1;
		$list[0]['_id_'] = 0;
		$list[0]['_name_'] = __('All');
		
		$list[0]['_agents_unknown_'] = 0;
		$list[0]['_monitors_alerts_fired_'] = 0;
		$list[0]['_total_agents_'] = 0;
		$list[0]['_monitors_ok_'] = 0;
		$list[0]['_monitors_critical_'] = 0;
		$list[0]['_monitors_warning_'] = 0;
		$list[0]['_monitors_unknown_'] = 0;
		$list[0]['_monitors_not_init_'] = 0;
		$list[0]['_agents_not_init_'] = 0;
		
		if ($mode == 'tactical') {
			$list[0]['_agents_ok_'] = 0;
			$list[0]['_agents_warning_'] = 0;
			$list[0]['_agents_critical_'] = 0;
			$list[0]['_monitors_alerts_'] = 0;
		}
	}
	else {
		$i = 0;
	}
	
	/* 
	 * Agent cache for metaconsole.
	 * Retrieve the statistic data from the cache table.
	 */
	if (!$user_strict && is_metaconsole() && !empty($list_groups)) {
		$cache_table = 'tmetaconsole_agent';
		
		$sql_stats = "SELECT id_grupo, COUNT(id_agente) AS agents_total,
						SUM(total_count) AS monitors_total,
						SUM(normal_count) AS monitors_ok,
						SUM(warning_count) AS monitors_warning,
						SUM(critical_count) AS monitors_critical,
						SUM(unknown_count) AS monitors_unknown,
						SUM(notinit_count) AS monitors_not_init,
						SUM(fired_count) AS alerts_fired
					  FROM $cache_table
					  WHERE disabled = 0
					  	AND id_grupo IN ($user_groups_ids)
					  GROUP BY id_grupo";
		$data_stats = db_get_all_rows_sql($sql_stats);
		
		$sql_stats_unknown = "SELECT id_grupo, COUNT(id_agente) AS agents_unknown
							  FROM $cache_table
							  WHERE disabled = 0
							  	AND id_grupo IN ($user_groups_ids)
							  	AND critical_count = 0
							  	AND warning_count = 0
							  	AND unknown_count > 0
							  GROUP BY id_grupo";
		$data_stats_unknown = db_get_all_rows_sql($sql_stats_unknown);
		
		$sql_stats_not_init = "SELECT id_grupo, COUNT(id_agente) AS agents_not_init
							  FROM $cache_table
							  WHERE disabled = 0
							  	AND id_grupo IN ($user_groups_ids)
							  	AND (total_count = 0 OR total_count = notinit_count)
							  GROUP BY id_grupo";
		$data_stats_not_init = db_get_all_rows_sql($sql_stats_not_init);
		
		if ($mode == 'tactical' || $mode == 'tree') {
			$sql_stats_ok = "SELECT id_grupo, COUNT(id_agente) AS agents_ok
							 FROM $cache_table
							 WHERE disabled = 0
							 	AND id_grupo IN ($user_groups_ids)
							 	AND critical_count = 0
							 	AND warning_count = 0
							 	AND unknown_count = 0
							 	AND normal_count > 0
							 GROUP BY id_grupo";
			$data_stats_ok = db_get_all_rows_sql($sql_stats_ok);
			
			$sql_stats_warning = "SELECT id_grupo, COUNT(id_agente) AS agents_warning
								  FROM $cache_table
								  WHERE disabled = 0
								  	AND id_grupo IN ($user_groups_ids)
								  	AND critical_count = 0
								  	AND warning_count > 0
								  GROUP BY id_grupo";
			$data_stats_warning = db_get_all_rows_sql($sql_stats_warning);
			
			$sql_stats_critical = "SELECT id_grupo, COUNT(id_agente) AS agents_critical
									FROM $cache_table
									WHERE disabled = 0
										AND id_grupo IN ($user_groups_ids)
										AND critical_count > 0
									GROUP BY id_grupo";
			$data_stats_critical = db_get_all_rows_sql($sql_stats_critical);
		}
		
		$stats_by_group = array();
		if (!empty($data_stats)) {
			foreach ($data_stats as $value) {
				$group_id = (int) $value['id_grupo'];
				
				$stats = array();
				$stats['agents_total'] = (int) $value['agents_total'];
				$stats['monitors_total'] = (int) $value['monitors_total'];
				$stats['monitors_ok'] = (int) $value['monitors_ok'];
				$stats['monitors_warning'] = (int) $value['monitors_warning'];
				$stats['monitors_critical'] = (int) $value['monitors_critical'];
				$stats['monitors_unknown'] = (int) $value['monitors_unknown'];
				$stats['monitors_not_init'] = (int) $value['monitors_not_init'];
				$stats['alerts_fired'] = (int) $value['alerts_fired'];
				$stats_by_group[$group_id] = $stats;
			}
			
			if (!empty($stats_by_group)) {
				if (!empty($data_stats_unknown)) {
					foreach ($data_stats_unknown as $value) {
						$group_id = (int) $value['id_grupo'];
						if (isset($stats_by_group[$group_id])) {
							$stats_by_group[$group_id]['agents_unknown'] = (int) $value['agents_unknown'];
						}
					}
				}
				if (!empty($data_stats_not_init)) {
					foreach ($data_stats_not_init as $value) {
						$group_id = (int) $value['id_grupo'];
						if (isset($stats_by_group[$group_id])) {
							$stats_by_group[$group_id]['agents_not_init'] = (int) $value['agents_not_init'];
						}
					}
				}
				if (!empty($data_stats_ok)) {
					foreach ($data_stats_ok as $value) {
						$group_id = (int) $value['id_grupo'];
						if (isset($stats_by_group[$group_id])) {
							$stats_by_group[$group_id]['agents_ok'] = (int) $value['agents_ok'];
						}
					}
				}
				if (!empty($data_stats_warning)) {
					foreach ($data_stats_warning as $value) {
						$group_id = (int) $value['id_grupo'];
						if (isset($stats_by_group[$group_id])) {
							$stats_by_group[$group_id]['agents_warning'] = (int) $value['agents_warning'];
						}
					}
				}
				if (!empty($data_stats_critical)) {
					foreach ($data_stats_critical as $value) {
						$group_id = (int) $value['id_grupo'];
						if (isset($stats_by_group[$group_id])) {
							$stats_by_group[$group_id]['agents_critical'] = (int) $value['agents_critical'];
						}
					}
				}
			}
		}
	}
	
	foreach ($list_groups as $key => $item) {
		$id = $item['id_grupo'];
		
		if (!$user_strict && is_metaconsole()) { // Agent cache
			$group_stat = array();
			if (isset($stats_by_group[$id]))
				$group_stat = $stats_by_group[$id];
			
			$list[$i]['_id_'] = $id;
			$list[$i]['_name_'] = $item['nombre'];
			$list[$i]['_iconImg_'] = html_print_image ("images/groups_small/" . groups_get_icon($item['id_grupo']).".png", true, array ("style" => 'vertical-align: middle;'));
			
			if ($mode == 'tree' && !empty($item['parent']))
				$list[$i]['_parent_id_'] = $item['parent'];
			
			$list[$i]['_agents_unknown_'] = isset($group_stat['agents_unknown']) ? $group_stat['agents_unknown'] : 0;
			$list[$i]['_monitors_alerts_fired_'] = isset($group_stat['alerts_fired']) ? $group_stat['alerts_fired'] : 0;
			$list[$i]['_total_agents_'] = isset($group_stat['agents_total']) ? $group_stat['agents_total'] : 0;
			
			// This fields are not in database
			$list[$i]['_monitors_ok_'] = isset($group_stat['monitors_ok']) ? $group_stat['monitors_ok'] : 0;
			$list[$i]['_monitors_critical_'] = isset($group_stat['monitors_critical']) ? $group_stat['monitors_critical'] : 0;
			$list[$i]['_monitors_warning_'] = isset($group_stat['monitors_warning']) ? $group_stat['monitors_warning'] : 0;
			$list[$i]['_monitors_unknown_'] = isset($group_stat['monitors_unknown']) ? $group_stat['monitors_unknown'] : 0;
			$list[$i]['_monitors_not_init_'] = isset($group_stat['monitors_not_init']) ? $group_stat['monitors_not_init'] : 0;
			$list[$i]['_agents_not_init_'] = isset($group_stat['agents_not_init']) ? $group_stat['agents_not_init'] : 0;
			
			if ($mode == 'tactical' || $mode == 'tree') {
				$list[$i]['_agents_ok_'] = isset($group_stat['agents_ok']) ? $group_stat['agents_ok'] : 0;
				$list[$i]['_agents_warning_'] = isset($group_stat['agents_warning']) ? $group_stat['agents_warning'] : 0;
				$list[$i]['_agents_critical_'] = isset($group_stat['agents_critical']) ? $group_stat['agents_critical'] : 0;
				$list[$i]['_monitors_alerts_'] =  isset($group_stat['alerts']) ? $group_stat['alerts'] : 0;;
				
				$list[$i]["_monitor_alerts_fire_count_"] = $group_stat[0]["alerts_fired"];
				$list[$i]["_total_checks_"] = $group_stat[0]["modules"];
				$list[$i]["_total_alerts_"] = $group_stat[0]["alerts"];
			}
			if ($mode == 'tactical') {
				// Get total count of monitors for this group, except disabled.	
				$list[$i]["_monitor_checks_"] = $list[$i]["_monitors_not_init_"] + $list[$i]["_monitors_unknown_"] + $list[$i]["_monitors_warning_"] + $list[$i]["_monitors_critical_"] + $list[$i]["_monitors_ok_"];
				
				// Calculate not_normal monitors
				$list[$i]["_monitor_not_normal_"] = $list[$i]["_monitor_checks_"] - $list[$i]["_monitors_ok_"];
				
				if ($list[$i]["_monitor_not_normal_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_monitor_health_"] = format_numeric (100 - ($list[$i]["_monitor_not_normal_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_monitor_health_"] = 100;
				}
				
				if ($list[$i]["_monitors_not_init_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_module_sanity_"] = format_numeric (100 - ($list[$i]["_monitors_not_init_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_module_sanity_"] = 100;
				}
				
				if (isset($list[$i]["_alerts_"])) {
					if ($list[$i]["_monitors_alerts_fired_"] > 0 && $list[$i]["_alerts_"] > 0) {
						$list[$i]["_alert_level_"] = format_numeric (100 - ($list[$i]["_monitors_alerts_fired_"] / ($list[$i]["_alerts_"] / 100)), 1);
					}
					else {
						$list[$i]["_alert_level_"] = 100;
					}
				} 
				else {
					$list[$i]["_alert_level_"] = 100;
					$list[$i]["_alerts_"] = 0;
				}
				
				$list[$i]["_monitor_bad_"] = $list[$i]["_monitors_critical_"] + $list[$i]["_monitors_warning_"];
				
				if ($list[$i]["_monitor_bad_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_global_health_"] = format_numeric (100 - ($list[$i]["_monitor_bad_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_global_health_"] = 100;
				}
				
				$list[$i]["_server_sanity_"] = format_numeric (100 - $list[$i]["_module_sanity_"], 1);
			}
			
			if ($returnAllGroup) {
				$list[0]['_agents_unknown_'] += $list[$i]['_agents_unknown_'];
				$list[0]['_monitors_alerts_fired_'] += $list[$i]['_monitors_alerts_fired_'];
				$list[0]['_total_agents_'] += $list[$i]['_total_agents_'];
				$list[0]['_monitors_ok_'] += $list[$i]['_monitors_ok_'];
				$list[0]['_monitors_critical_'] += $list[$i]['_monitors_critical_'];
				$list[0]['_monitors_warning_'] += $list[$i]['_monitors_warning_'];
				$list[0]['_monitors_unknown_'] += $list[$i]['_monitors_unknown_'];
				$list[0]['_monitors_not_init_'] += $list[$i]['_monitors_not_init_'];
				$list[0]['_agents_not_init_'] += $list[$i]['_agents_not_init_'];
				
				if ($mode == 'tactical' || $mode == 'tree') {
					$list[0]['_agents_ok_'] += $list[$i]['_agents_ok_'];
					$list[0]['_agents_warning_'] += $list[$i]['_agents_warning_'];
					$list[0]['_agents_critical_'] += $list[$i]['_agents_critical_'];
					$list[0]['_monitors_alerts_'] += $list[$i]['_monitors_alerts_'];
				}
			}
			
			if ($mode == 'group') {
				if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0)
						&& ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0)
						&& ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0)) {
					unset($list[$i]);
				}
			}
			
		}
		else if (($config["realtimestats"] == 0) && !$user_strict) {
			$group_stat = db_get_all_rows_sql ("SELECT *
				FROM tgroup_stat, tgrupo
				WHERE tgrupo.id_grupo = tgroup_stat.id_group
					AND tgroup_stat.id_group = $id
				ORDER BY nombre");
			
			$list[$i]['_id_'] = $id;
			$list[$i]['_name_'] = $item['nombre'];
			$list[$i]['_iconImg_'] = html_print_image ("images/groups_small/" . groups_get_icon($item['id_grupo']).".png", true, array ("style" => 'vertical-align: middle;'));
			
			if ($mode == 'tree' && !empty($item['parent']))
				$list[$i]['_parent_id_'] = $item['parent'];
			
			$list[$i]['_agents_unknown_'] = $group_stat[0]["unknown"];
			$list[$i]['_monitors_alerts_fired_'] = $group_stat[0]["alerts_fired"];
			$list[$i]['_total_agents_'] = $group_stat[0]["agents"];
			
			// This fields are not in database
			$list[$i]['_monitors_ok_'] = (int) groups_get_normal_monitors($id);
			$list[$i]['_monitors_critical_'] = (int) groups_get_critical_monitors($id);
			$list[$i]['_monitors_warning_'] = (int) groups_get_warning_monitors($id);
			$list[$i]['_monitors_unknown_'] = (int) groups_get_unknown_monitors($id);
			$list[$i]['_monitors_not_init_'] = (int) groups_get_not_init_monitors($id);
			$list[$i]['_agents_not_init_'] = (int) groups_get_not_init_agents($id);
			
			if ($mode == 'tactical' || $mode == 'tree') {
				$list[$i]['_agents_ok_'] = $group_stat[0]["normal"];
				$list[$i]['_agents_warning_'] = $group_stat[0]["warning"];
				$list[$i]['_agents_critical_'] = $group_stat[0]["critical"];
				$list[$i]['_monitors_alerts_'] = $group_stat[0]["alerts"];
				
				$list[$i]["_monitor_alerts_fire_count_"] = $group_stat[0]["alerts_fired"];
				$list[$i]["_total_checks_"] = $group_stat[0]["modules"];
				$list[$i]["_total_alerts_"] = $group_stat[0]["alerts"];
			}
			if ($mode == 'tactical') {
				// Get total count of monitors for this group, except disabled.	
				$list[$i]["_monitor_checks_"] = $list[$i]["_monitors_not_init_"] + $list[$i]["_monitors_unknown_"] + $list[$i]["_monitors_warning_"] + $list[$i]["_monitors_critical_"] + $list[$i]["_monitors_ok_"];
				
				// Calculate not_normal monitors
				$list[$i]["_monitor_not_normal_"] = $list[$i]["_monitor_checks_"] - $list[$i]["_monitors_ok_"];
				
				if ($list[$i]["_monitor_not_normal_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_monitor_health_"] = format_numeric (100 - ($list[$i]["_monitor_not_normal_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_monitor_health_"] = 100;
				}
				
				if ($list[$i]["_monitors_not_init_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_module_sanity_"] = format_numeric (100 - ($list[$i]["_monitors_not_init_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_module_sanity_"] = 100;
				}
				
				if (isset($list[$i]["_alerts_"])) {
					if ($list[$i]["_monitors_alerts_fired_"] > 0 && $list[$i]["_alerts_"] > 0) {
						$list[$i]["_alert_level_"] = format_numeric (100 - ($list[$i]["_monitors_alerts_fired_"] / ($list[$i]["_alerts_"] / 100)), 1);
					}
					else {
						$list[$i]["_alert_level_"] = 100;
					}
				} 
				else {
					$list[$i]["_alert_level_"] = 100;
					$list[$i]["_alerts_"] = 0;
				}
				
				$list[$i]["_monitor_bad_"] = $list[$i]["_monitors_critical_"] + $list[$i]["_monitors_warning_"];
				
				if ($list[$i]["_monitor_bad_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_global_health_"] = format_numeric (100 - ($list[$i]["_monitor_bad_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_global_health_"] = 100;
				}
				
				$list[$i]["_server_sanity_"] = format_numeric (100 - $list[$i]["_module_sanity_"], 1);
			}
			
			if ($returnAllGroup) {
				$list[0]['_agents_unknown_'] += $group_stat[0]["unknown"];
				$list[0]['_monitors_alerts_fired_'] += $group_stat[0]["alerts_fired"];
				$list[0]['_total_agents_'] += $group_stat[0]["agents"];
				$list[0]['_monitors_ok_'] += $list[$i]['_monitors_ok_'];
				$list[0]['_monitors_critical_'] += $list[$i]['_monitors_critical_'];
				$list[0]['_monitors_warning_'] += $list[$i]['_monitors_warning_'];
				$list[0]['_monitors_unknown_'] += $list[$i]['_monitors_unknown_'];
				$list[0]['_monitors_not_init_'] += $list[$i]['_monitors_not_init_'];
				$list[0]['_agents_not_init_'] += $list[$i]['_agents_not_init_'];
				
				if ($mode == 'tactical' || $mode == 'tree') {
					$list[0]['_agents_ok_'] += $group_stat[0]["normal"];
					$list[0]['_agents_warning_'] += $group_stat[0]["warning"];
					$list[0]['_agents_critical_'] += $group_stat[0]["critical"];
					$list[0]['_monitors_alerts_'] += $group_stat[0]["alerts"];
				}
			}
			
			if ($mode == 'group')  {
				if (! defined ('METACONSOLE')) {
					if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0) && ($list[$i]['_monitors_unknown_'] == 0) && ($list[$i]['_monitors_not_init_'] == 0) && ($list[$i]['_agents_not_init_'] == 0)) {
						unset($list[$i]);
					}
				}
				else {
					if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0)) {
						unset($list[$i]);
					}
				}
			}
			
		}
		else {
			$list[$i]['_id_'] = $id;
			$list[$i]['_name_'] = $item['nombre'];
			$list[$i]['_iconImg_'] = html_print_image ("images/groups_small/" . groups_get_icon($item['id_grupo']).".png", true, array ("style" => 'vertical-align: middle;'));
			
			if ($mode == 'tree' && !empty($item['parent']))
				$list[$i]['_parent_id_'] = $item['parent'];
			
			$list[$i]['_monitors_ok_'] = (int) groups_get_normal_monitors ($id, $agent_filter, $module_filter, $user_strict, $acltags, $config["realtimestats"]);
			$list[$i]['_monitors_critical_'] = (int) groups_get_critical_monitors ($id, $agent_filter, $module_filter, $user_strict, $acltags, $config["realtimestats"]);
			$list[$i]['_monitors_warning_'] = (int) groups_get_warning_monitors ($id, $agent_filter, $module_filter, $user_strict, $acltags, $config["realtimestats"]);
			$list[$i]['_monitors_unknown_'] = (int) groups_get_unknown_monitors ($id, $agent_filter, $module_filter, $user_strict, $acltags, $config["realtimestats"]);
			$list[$i]['_monitors_not_init_'] = (int) groups_get_not_init_monitors ($id, $agent_filter, $module_filter, $user_strict, $acltags, $config["realtimestats"]);
			$list[$i]['_monitors_alerts_fired_'] = groups_monitor_fired_alerts ($id, $user_strict, $id);
			$list[$i]['_total_agents_'] = (int) groups_get_total_agents ($id, $agent_filter, $module_filter, $user_strict, $acltags, $config["realtimestats"]);
			$list[$i]['_agents_unknown_'] = (int) groups_get_unknown_agents ($id, $agent_filter, $module_filter, $user_strict, $acltags, $config["realtimestats"]);
			$list[$i]['_agents_not_init_'] = (int) groups_get_not_init_agents ($id, $agent_filter, $module_filter, $user_strict, $acltags, $config["realtimestats"]);
			
			if ($mode == 'tactical' || $mode == 'tree') {
				$list[$i]['_agents_ok_'] = (int) groups_get_normal_agents ($id, $agent_filter, $module_filter, $user_strict, $acltags, $config["realtimestats"]);
				$list[$i]['_agents_warning_'] = (int) groups_get_warning_agents ($id, $agent_filter, $module_filter, $user_strict, $acltags, $config["realtimestats"]);
				$list[$i]['_agents_critical_'] = (int) groups_get_critical_agents ($id, $agent_filter, $module_filter, $user_strict, $acltags, $config["realtimestats"]);
				$list[$i]['_monitors_alerts_'] = groups_monitor_alerts ($id, $user_strict, $id);
				
				// TODO
				//~ $list[$i]["_total_checks_"] 
				//~ $list[$i]["_total_alerts_"]
				
				// Get total count of monitors for this group, except disabled.	
				$list[$i]["_monitor_checks_"] = $list[$i]["_monitors_not_init_"] + $list[$i]["_monitors_unknown_"] + $list[$i]["_monitors_warning_"] + $list[$i]["_monitors_critical_"] + $list[$i]["_monitors_ok_"];
				
				// Calculate not_normal monitors
				$list[$i]["_monitor_not_normal_"] = $list[$i]["_monitor_checks_"] - $list[$i]["_monitors_ok_"];
				
				if ($list[$i]["_monitor_not_normal_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_monitor_health_"] = format_numeric (100 - ($list[$i]["_monitor_not_normal_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_monitor_health_"] = 100;
				}
				
				if ($list[$i]["_monitors_not_init_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_module_sanity_"] = format_numeric (100 - ($list[$i]["_monitors_not_init_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_module_sanity_"] = 100;
				}
				
				if (isset($list[$i]["_alerts_"])) {
					if ($list[$i]["_monitors_alerts_fired_"] > 0 && $list[$i]["_alerts_"] > 0) {
						$list[$i]["_alert_level_"] = format_numeric (100 - ($list[$i]["_monitors_alerts_fired_"] / ($list[$i]["_alerts_"] / 100)), 1);
					}
					else {
						$list[$i]["_alert_level_"] = 100;
					}
				} 
				else {
					$list[$i]["_alert_level_"] = 100;
					$list[$i]["_alerts_"] = 0;
				}
				
				$list[$i]["_monitor_bad_"] = $list[$i]["_monitors_critical_"] + $list[$i]["_monitors_warning_"];
				
				if ($list[$i]["_monitor_bad_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_global_health_"] = format_numeric (100 - ($list[$i]["_monitor_bad_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_global_health_"] = 100;
				}
				
				$list[$i]["_server_sanity_"] = format_numeric (100 - $list[$i]["_module_sanity_"], 1);
			}
			
			if ($returnAllGroup) {
				$list[0]['_agents_unknown_'] += $list[$i]['_agents_unknown_'];
				$list[0]['_monitors_alerts_fired_'] += $list[$i]['_monitors_alerts_fired_'];
				$list[0]['_total_agents_'] += $list[$i]['_total_agents_'];
				$list[0]['_monitors_ok_'] += $list[$i]['_monitors_ok_'];
				$list[0]['_monitors_critical_'] += $list[$i]['_monitors_critical_'];
				$list[0]['_monitors_warning_'] += $list[$i]['_monitors_warning_'];
				$list[0]['_monitors_unknown_'] += $list[$i]['_monitors_unknown_'];
				$list[0]['_monitors_not_init_'] = $list[$i]['_monitors_not_init_'];
				$list[0]['_agents_not_init_'] += $list[$i]['_agents_not_init_'];
				
				if ($mode == 'tactical' || $mode == 'tree') {
					$list[0]['_agents_ok_'] += $list[$i]['_agents_ok_'];
					$list[0]['_agents_warning_'] += $list[$i]['_agents_warning_'];
					$list[0]['_agents_critical_'] += $list[$i]['_agents_critical_'];
					$list[0]['_monitors_alerts_'] += $list[$i]['_monitors_alerts_'];
				}
			}
			
			if ($mode == 'group') {
				if (! defined ('METACONSOLE')) {
					if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0) && ($list[$i]['_monitors_unknown_'] == 0) && ($list[$i]['_monitors_not_init_'] == 0) && ($list[$i]['_agents_not_init_'] == 0)) {
						unset($list[$i]);
					}
				} else {
					if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0)) {
						unset($list[$i]);
					}
				}
			}
		}
		$i++;
	}
	
	if ($user_strict) {
		foreach ($user_tags as $group_id => $tag_name) {
			$id = db_get_value('id_tag', 'ttag', 'name', $tag_name);
			
			$list[$i]['_id_'] = $id;
			$list[$i]['_name_'] = $tag_name;
			$list[$i]['_iconImg_'] = html_print_image ("images/tag_red.png", true, array ("style" => 'vertical-align: middle;'));
			$list[$i]['_is_tag_'] = 1;
			
			$list[$i]['_total_agents_'] = (int) tags_get_total_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
			$list[$i]['_agents_unknown_'] = (int) tags_get_unknown_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
			$list[$i]['_agents_not_init_'] = (int) tags_get_not_init_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
			$list[$i]['_monitors_ok_'] = (int) tags_get_normal_monitors ($id, $acltags, $agent_filter, $module_filter);
			$list[$i]['_monitors_critical_'] = (int) tags_get_critical_monitors ($id, $acltags, $agent_filter, $module_filter);
			$list[$i]['_monitors_warning_'] = (int) tags_get_warning_monitors ($id, $acltags, $agent_filter, $module_filter);
			$list[$i]['_monitors_not_init_'] = (int) tags_get_not_init_monitors ($id, $acltags, $agent_filter, $module_filter);
			$list[$i]['_monitors_unknown_'] = (int) tags_get_unknown_monitors ($id, $acltags, $agent_filter, $module_filter);
			$list[$i]['_monitors_alerts_fired_'] = tags_monitors_fired_alerts($id, $acltags);
			
			if ($mode == 'tactical' || $mode == 'tree') {
				$list[$i]['_agents_ok_'] = (int) tags_get_normal_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
				$list[$i]['_agents_warning_'] = (int) tags_get_warning_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
				$list[$i]['_agents_critical_'] = (int) tags_get_critical_agents ($id, $acltags, $agent_filter, $module_filter, $config["realtimestats"]);
				$list[$i]['_monitors_alerts_'] = tags_get_monitors_alerts ($id, $acltags);
			}
			if ($mode == 'tactical') {
				// Get total count of monitors for this group, except disabled.	
				$list[$i]["_monitor_checks_"] = $list[$i]["_monitors_not_init_"] + $list[$i]["_monitors_unknown_"] + $list[$i]["_monitors_warning_"] + $list[$i]["_monitors_critical_"] + $list[$i]["_monitors_ok_"];
				
				// Calculate not_normal monitors
				$list[$i]["_monitor_not_normal_"] = $list[$i]["_monitor_checks_"] - $list[$i]["_monitors_ok_"];
				
				if ($list[$i]["_monitor_not_normal_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_monitor_health_"] = format_numeric (100 - ($list[$i]["_monitor_not_normal_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_monitor_health_"] = 100;
				}
				
				if ($list[$i]["_monitors_not_init_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_module_sanity_"] = format_numeric (100 - ($list[$i]["_monitors_not_init_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_module_sanity_"] = 100;
				}
				
				if (isset($list[$i]["_monitors_alerts_"])) {
					if ($list[$i]["_monitors_alerts_fired_"] > 0 && $list[$i]["_monitors_alerts_"] > 0) {
						$list[$i]["_alert_level_"] = format_numeric (100 - ($list[$i]["_monitors_alerts_fired_"] / ($list[$i]["_monitors_alerts_"] / 100)), 1);
					}
					else {
						$list[$i]["_alert_level_"] = 100;
					}
				} 
				else {
					$list[$i]["_alert_level_"] = 100;
					$list[$i]["_monitors_alerts_"] = 0;
				}
				
				$list[$i]["_monitor_bad_"] = $list[$i]["_monitors_critical_"] + $list[$i]["_monitors_warning_"];
				
				if ($list[$i]["_monitor_bad_"] > 0 && $list[$i]["_monitor_checks_"] > 0) {
					$list[$i]["_global_health_"] = format_numeric (100 - ($list[$i]["_monitor_bad_"] / ($list[$i]["_monitor_checks_"] / 100)), 1);
				}
				else {
					$list[$i]["_global_health_"] = 100;
				}
				
				$list[$i]["_server_sanity_"] = format_numeric (100 - $list[$i]["_module_sanity_"], 1);
			}
			
			if ($returnAllGroup) {
				$list[0]['_agents_unknown_'] += $list[$i]['_agents_unknown_'];
				$list[0]['_monitors_alerts_fired_'] += $list[$i]['_monitors_alerts_fired_'];
				$list[0]['_total_agents_'] += $list[$i]['_total_agents_'];
				$list[0]['_monitors_ok_'] += $list[$i]['_monitors_ok_'];
				$list[0]['_monitors_critical_'] += $list[$i]['_monitors_critical_'];
				$list[0]['_monitors_warning_'] += $list[$i]['_monitors_warning_'];
				$list[0]['_monitors_unknown_'] += $list[$i]['_monitors_unknown_'];
				$list[0]['_agents_not_init_'] += $list[$i]['_agents_not_init_'];
				$list[0]['_monitors_not_init_'] += $list[$i]['_monitors_not_init_'];
				
				if ($mode == 'tactical' || $mode == 'tree') {
					$list[0]['_agents_ok_'] += $list[$i]['_agents_ok_'];
					$list[0]['_agents_warning_'] += $list[$i]['_agents_warning_'];
					$list[0]['_agents_critical_'] += $list[$i]['_agents_critical_'];
					$list[0]['_monitors_alerts_'] += $list[$i]['_monitors_alerts_'];
				}
			}
			
			if ($mode == 'group') {
				if (! defined ('METACONSOLE')) {
					if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0) && ($list[$i]['_monitors_unknown_'] == 0) && ($list[$i]['_monitors_not_init_'] == 0) && ($list[$i]['_agents_not_init_'] == 0)) {
						unset($list[$i]);
					}
				}
				else {
					if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0)) {
						unset($list[$i]);
					}
				}
			}
			$i++;
		}
	}
	
	return $list;
}

function group_get_groups_list($id_user = false, $user_strict = false, $access = 'AR', $force_group_and_tag = true, $returnAllGroup = false, $mode = 'group') {
	global $config;
	
	if ($id_user == false) {
		$id_user = $config['id_user'];
	}
	
	$acltags = tags_get_user_module_and_tags ($id_user, $access, $user_strict);
	
	// If using metaconsole, the strict users will use the agent table of every node
	if (is_metaconsole() && $user_strict) {
		$servers = metaconsole_get_servers();
		
		$result_list = array ();
		foreach ($servers as $server) {
			
			if (metaconsole_connect($server) != NOERR) {
				continue;
			}
			$server_list = group_get_data ($id_user, $user_strict,
				$acltags, $returnAllGroup, $mode);
			
			foreach ($server_list as $server_item) {
				if (! isset ($result_list[$server_item['_name_']])) {
					
					$result_list[$server_item['_name_']] = $server_item;
				}
				else {
					$result_list[$server_item['_name_']]['_monitors_ok_'] += $server_item['_monitors_ok_'];
					$result_list[$server_item['_name_']]['_monitors_critical_'] += $server_item['_monitors_critical_'];
					$result_list[$server_item['_name_']]['_monitors_warning_'] += $server_item['_monitors_warning_'];
					$result_list[$server_item['_name_']]['_agents_unknown_'] += $server_item['_agents_unknown_'];
					$result_list[$server_item['_name_']]['_total_agents_'] += $server_item['_total_agents_'];
					$result_list[$server_item['_name_']]['_monitors_alerts_fired_'] += $server_item['_monitors_alerts_fired_'];
					
					if ($mode == 'tactical') {
						$result_list[$server_item['_name_']]['_agents_ok_'] += $server_item['_agents_ok_'];
						$result_list[$server_item['_name_']]['_agents_critical_'] += $server_item['_agents_critical_'];
						$result_list[$server_item['_name_']]['_agents_warning_'] += $server_item['_agents_warning_'];
						$result_list[$server_item['_name_']]['_monitors_alerts_'] += $server_item['_monitors_alerts_'];
						
						$result_list[$server_item['_name_']]["_monitor_checks_"] += $server_item["_monitor_checks_"];
						$result_list[$server_item['_name_']]["_monitor_not_normal_"] += $server_item["_monitor_not_normal_"];
						$result_list[$server_item['_name_']]["_monitor_health_"] += $server_item["_monitor_health_"];
						$result_list[$server_item['_name_']]["_module_sanity_"] += $server_item["_module_sanity_"];
						$result_list[$server_item['_name_']]["_alerts_"] += $server_item["_alerts_"];
						$result_list[$server_item['_name_']]["_alert_level_"] += $server_item["_alert_level_"];
						$result_list[$server_item['_name_']]["_monitor_bad_"] += $server_item["_monitor_bad_"];
						$result_list[$server_item['_name_']]["_global_health_"] += $server_item["_global_health_"];
						$result_list[$server_item['_name_']]["_server_sanity_"] += $server_item["_server_sanity_"];
						$result_list[$server_item['_name_']]["_monitor_alerts_fire_count_"] += $server_item["_monitor_alerts_fire_count_"];
						$result_list[$server_item['_name_']]["_total_checks_"] += $server_item["_total_checks_"];
						$result_list[$server_item['_name_']]["_total_alerts_"] += $server_item["_total_alerts_"];
					}
				}
			}
			metaconsole_restore_db();
			
		}
		
		return $result_list;
	}
	// If using metaconsole, the not strict users will use the metaconsole's agent cache table
	else {
		$result_list = group_get_data ($id_user, $user_strict, $acltags,
			$returnAllGroup, $mode);
		
		return $result_list;
	}
}

function groups_get_group_deep ($id_group) {
	global $config;
	$parents = groups_get_parents($id_group, false);
	
	if (empty($parents)) {
		$deep = "";
	}
	else {
		$deep = str_repeat("&nbsp;&nbsp;", count($parents));
	}
	
	return $deep;
}
?>
