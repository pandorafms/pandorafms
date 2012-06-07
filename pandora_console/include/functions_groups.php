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
			$numRows = db_get_num_rows('SELECT * FROM tagente WHERE id_grupo = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM tagente WHERE id_grupo = ' . $idGroup);
			break;
	}
	if ($numRows > 0) {
		$return['return'] = true;
		$return['tables'][] = __('Agents'); 
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$numRows = db_get_num_rows('SELECT * FROM talert_actions WHERE id_group = ' . $idGroup . ';');
			break;
		case "oracle":
			$numRows = db_get_num_rows('SELECT * FROM talert_actions WHERE id_group = ' . $idGroup);
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
function groups_get_childrens($parent, $groups = null) {
	if (empty($groups)) {
		$groups = db_get_all_rows_in_table('tgrupo');
	}

	$return = array();

	foreach ($groups as $key => $group) {
		if ($group['id_grupo'] == 0) {
			continue;
		}
		if ($group['parent'] == $parent) {
			$return = $return + array($group['id_grupo'] => $group) + groups_get_childrens($group['id_grupo'], $groups);
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
			$return = $return + array($group['id_grupo'] => $group) + groups_get_parents($group['parent'], $groups);
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

	if($rows === false) {
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

/**
 * Make with a list of groups a treefied list of groups.
 *
 * @param array $groups The list of groups to create the treefield list.
 * @param integer $parent The id_group of parent actual scan branch.
 * @param integer $deep The level of profundity in the branch.
 *
 * @return array The treefield list of groups.
 */
function groups_get_groups_tree_recursive($groups, $parent = 0, $deep = 0) {
	$return = array();

	foreach ($groups as $key => $group) {
		if (($key == 0) && ($parent == 0)) { //When the groups is the all group
			$group['deep'] = $deep;
			$group['hash_branch'] = true;
			$deep ++;
			$return = $return + array($key => $group);
		}
		else if ($group['parent'] == $parent) {
			$group['deep'] = $deep;
			$branch = groups_get_groups_tree_recursive($groups, $key, $deep + 1);
			if (empty($branch)) {
				$group['hash_branch'] = false;
			}
			else {
				$group['hash_branch'] = true;
			}
			$return = $return + array($key => $group) + $branch;
		}
	}

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
	$agents = agents_get_group_agents($id_group);

	$agents_status = array();
	foreach($agents as $key => $agent){
		$agents_status[] = agents_get_status($key);
	}

	$childrens = groups_get_childrens($id_group);

	foreach($childrens as $key => $child){
		$agents_status[] = groups_get_status($key);
	}

	// Status is 0 for normal, 1 for critical, 2 for warning and 3/-1 for unknown. 4 for fired alerts

	// Checking if any agent has fired alert (4)
	if(is_int(array_search(4,$agents_status))){
		return 4;
	}
	// Checking if any agent has critical status (1)
	elseif(is_int(array_search(1,$agents_status))){
		return 1;
	}
	// Checking if any agent has warning status (2)
	elseif(is_int(array_search(2,$agents_status))){
		return 2;
	}
	// Checking if any agent has unknown status (-1)
	elseif(is_int(array_search(-1,$agents_status))){
		return -1;
	}
	// Checking if any agents module has unknown status (3)
	elseif(is_int(array_search(3,$agents_status))){
		return 3;
	}
	else {
		return 0;
	}

	return $status;
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
	if($id_group > 0)
		return (string) db_get_value ('nombre', 'tgrupo', 'id_grupo', (int) $id_group);
	elseif($returnAllGroup)
		return "All";
}

/**
 * Get all the users belonging to a group.
 *
 * @param int $id_group The group id to look for
 *
 * @return array An array with all the users or an empty array
 */
function groups_get_users ($id_group, $filter = false) {
	global $config;
	
	if (! is_array ($filter))
		$filter = array ();
	
	$filter['id_grupo'] = $id_group;
	
	$resulta = array();
	$resulta = db_get_all_rows_filter ("tusuario_perfil", $filter);

	// The users of the group All (0) will be also returned
	$filter['id_grupo'] = 0;
	$resultb = array();
	$resultb = db_get_all_rows_filter ("tusuario_perfil", $filter);

	if($resulta == false && $resultb == false)
		$result = false;
	elseif($resulta == false)
		$result = $resultb;
	elseif($resultb == false)
		$result = $resulta;
	else
		$result = array_merge($resulta,$resultb);

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
	if ($data["monitor_alerts_fired"] > 0){
		echo "<tr class='group_view_alrm' style='height: 35px;'>";
	}
	elseif ($data["monitor_critical"] > 0) {
		echo "<tr class='group_view_crit' style='height: 35px;'>"; //background-color: #ffc0b5;
	}
	elseif ($data["monitor_warning"] > 0) {
		echo "<tr class='group_view_warn' style='height: 35px;'>";
	}
	elseif (($data["monitor_unknown"] > 0) ||  ($data["agents_unknown"] > 0)) {
		echo "<tr class='group_view_unk' style='height: 35px;'>";
	}
	elseif ($data["monitor_ok"] > 0)  {
		echo "<tr class='group_view_ok' style='height: 35px;'>";
	}
	else {
		echo "<tr class='group_view_normal' style='height: 35px;'>";
	}

	// Group name
	echo "<td class='group_view_data' style='font-weight: bold; font-size: 12px;'>&nbsp;&nbsp;";
	echo $group['prefix'].ui_print_group_icon ($id_group, true, "groups_small", 'font-size: 7.5pt');
	echo "&nbsp;<a class='group_view_data' href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=$id_group'>";
	echo ui_print_truncate_text($group['name'], 35);
	echo "</a>";
	echo "</td>";
	echo "<td class='group_view_data'  style='text-align: center; vertica-align: middle;'>";
	if (check_acl ($config['id_user'], $id_group, "AW")) {
		echo '<a href="index.php?sec=estado&sec2=operation/agentes/group_view&update_netgroup='.$id_group.'">' . html_print_image("images/target.png", true, array("border" => '0', "alt" => __('Force'))) . '</a>';
	}
	echo "</td>";

	// Total agents
	echo "<td class='group_view_data' class='group_view_data' style='font-weight: bold; font-size: 18px; text-align: center;'>";
	if ($data["total_agents"] > 0)
		echo "<a class='group_view_data' style='font-weight: bold; font-size: 18px; text-align: center;' 
			href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=$id_group'>";

		//Total agent field given by function reporting_get_group_stats return the number of agents
		//of this groups and its children. It was done to print empty fathers of children groups.
		//We need to recalculate the total agents for this group here to get only the total agents
		//for this group. Of course the group All (0) is a special case.

		$data["total_agents"];

		if($id_group != 0) {
	
			$data["total_agents"] = db_get_sql ("SELECT COUNT(id_agente) FROM tagente 
							WHERE id_grupo = $id_group AND disabled = 0");
		} else {
			$data["total_agents"] = db_get_sql ("SELECT COUNT(id_agente) FROM tagente 
							WHERE disabled = 0");
		}

		echo $data["total_agents"];
		echo "</a>";

	// Agents unknown
	if ($data["agents_unknown"] > 0) {
		echo "<td class='group_view_data_unk' style='font-weight: bold; font-size: 18px; text-align: center;'>";
		echo "<a class='group_view_data_unk' style='font-weight: bold; font-size: 18px; text-align: center;' 
                        href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=$id_group&status=3'>";
		echo $data["agents_unknown"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td></td>";
	}

	// Monitors Unknown
	if ($data["monitor_unknown"] > 0){
		echo "<td class='group_view_data_unk' style='font-weight: bold; font-size: 18px; text-align: center;'>";
		echo "<a style='font-weight: bold; font-size: 18px; color: #666; text-align: center;'
			href='index.php?sec=estado&sec2=operation/agentes/status_monitor&ag_group=$id_group&status=3'>";
		echo $data["monitor_unknown"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td></td>";
	}


	// Monitors Not Init
	if ($data["monitor_not_init"] > 0){
		echo "<td class='group_view_data_unk' style='font-weight: bold; font-size: 18px; text-align: center;'>";
                echo "<a class='group_view_data_unk' style='font-weight: bold; font-size: 18px; text-align: center;'
                        href='index.php?sec=estado&sec2=operation/agentes/status_monitor&ag_group=$id_group&status=5'>";
		echo $data["monitor_not_init"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td></td>";
	}


	// Monitors OK
	echo "<td class='group_view_data_ok' style='font-weight: bold; font-size: 18px; text-align: center;'>";
	if ($data["monitor_ok"] > 0) {
                echo "<a class='group_view_data_unk' style='font-weight: bold; font-size: 18px; text-align: center;'                       href='index.php?sec=estado&sec2=operation/agentes/status_monitor&ag_group=$id_group&status=0'>";
		echo $data["monitor_ok"];
		echo "</a>";
	}
	else { 
		echo "&nbsp;";
	}
	echo "</td>";

	// Monitors Warning
	if ($data["monitor_warning"] > 0){
		echo "<td class='group_view_data_warn' style='font-weight: bold; font-size: 18px; text-align: center;'>";
                echo "<a class='group_view_data_warn' class='group_view_data_unk' style='font-weight: bold; font-size: 18px; text-align: center;'
                        href='index.php?sec=estado&sec2=operation/agentes/status_monitor&ag_group=$id_group&status=1'>";
		echo $data["monitor_warning"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td></td>";
	}

	// Monitors Critical
	if ($data["monitor_critical"] > 0){
		echo "<td class='group_view_data_crit' style='font-weight: bold; font-size: 18px; text-align: center;'>";
                echo "<a class='group_view_data_crit' style='font-weight: bold; font-size: 18px; text-align: center;'
                        href='index.php?sec=estado&sec2=operation/agentes/status_monitor&ag_group=$id_group&status=2'>";
		echo $data["monitor_critical"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td></td>";
	}
	// Alerts fired
	if ($data["monitor_alerts_fired"] > 0){
		echo "<td class='group_view_data_alrm' style='font-weight: bold; font-size: 18px;  text-align: center;'>";
		echo "<a class='group_view_data_alrm' style='font-weight: bold; font-size: 18px; text-align: center;'
			href='index.php?sec=estado&sec2=operation/agentes/alerts_status&ag_group=$id_group&filter=fired'>";
		echo $data["monitor_alerts_fired"];
		echo "</a>";
		echo "</td>";
	}
	else {
		echo "<td></td>";
	}


	echo "</tr>";
	echo "<tr style='height: 5px;'><td colspan=10> </td></tr>";
	
	foreach($group['childs'] as $child) {
		groups_get_group_row($child, $group_all, $group_all[$child], $printed_groups);
	}
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
	
	if ($group_name == ""){
		return false;
	}
	
	$array_tmp = array('nombre' => $group_name);
	
	$values = array_merge($rest_values, $array_tmp);

	$check = db_get_value('nombre', 'tgrupo', 'nombre', $group_name);
	
	if (!$check){
		$result = db_process_sql_insert('tgrupo', $values);
	} else {
		$result = false;
	}
	
	return $result;
}


// Get unknown agents by using the status code in modules.

function groups_agent_unknown ($group_array) {
	
	if (empty ($group_array)) {
		return 0;
		
	} else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
			
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT( DISTINCT tagente_estado.id_agente) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 3 AND tagente_estado.id_agente = tagente.id_agente AND tagente.id_grupo IN $group_clause");
	
}

// Get ok agents by using the status code in modules.

function groups_agent_ok ($group_array) {
	
	if (empty ($group_array)) {
		return 0;
		
	} else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
			
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//!!!Query explanation!!!
	//An agent is OK if all its modules are OK
	//The status values are: 0 OK; 1 Critical; 2 Warning; 3 Unkown
	//This query grouped all modules by agents and select the MAX value for status which has the value 0 
	//If MAX(estado) is 0 it means all modules has status 0 => OK
	//Then we count the agents of the group selected to know how many agents are in OK status
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(max_estado) FROM (SELECT MAX(tagente_estado.estado) as max_estado FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente.id_grupo IN $group_clause GROUP BY tagente.id_agente HAVING max_estado = 0) AS S1");
	
}

// Get critical agents by using the status code in modules.

function groups_agent_critical ($group_array) {
	
	if (empty ($group_array)) {
		return 0;
		
	} else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
			
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//!!!Query explanation!!!
	//An agent is Warning when has at least one module in warning status and nothing more in critical status
	//The status values are: 0 OK; 1 Critical; 2 Warning; 3 Unkown
	//If estado = 1 it means at leas 1 module is in critical status so the agent is critical
	//Then we count the agents of the group selected to know how many agents are in critical status	
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT( DISTINCT tagente_estado.id_agente) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 1 AND tagente_estado.id_agente = tagente.id_agente AND tagente.id_grupo IN $group_clause");
	
}

// Get warning agents by using the status code in modules.

function groups_agent_warning ($group_array) {

	if (empty ($group_array)) {
		return 0;
		
	} else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
			
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//!!!Query explanation!!!
	//An agent is Warning when has at least one module in warning status and nothing more in critical status
	//The status values are: 0 OK; 1 Critical; 2 Warning; 3 Unkown
	//This query grouped all modules by agents and select the MIN value for status which has the value 0 
	//If MIN(estado) is 2 it means at least one module is warning and there is no critical modules
	//Then we count the agents of the group selected to know how many agents are in warning status
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(min_estado) FROM (SELECT MIN(tagente_estado.estado) as min_estado FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente.id_grupo IN $group_clause GROUP BY tagente.id_agente HAVING min_estado = 2) AS S1");
	
}


// Get monitor NOT INIT, except disabled AND async modules

function groups_monitor_not_init ($group_array) {

	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	} else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
			
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
				FROM tagente_estado, tagente, tagente_modulo
				WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0 
				    AND tagente.id_agente = tagente_estado.id_agente
					AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
					AND tagente_modulo.disabled = 0
					AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,24) AND utimestamp = 0");
	
}

// Get monitor OK, except disabled and non-init

function groups_monitor_ok ($group_array) {

	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	} else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
			
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
				FROM tagente_estado, tagente, tagente_modulo
				WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
					AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.estado = 0 
					AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.utimestamp != 0");	
}

// Get monitor CRITICAL, except disabled and non-init

function groups_monitor_critical ($group_array) {

	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	} else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
			
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
				FROM tagente_estado, tagente, tagente_modulo
				WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
					AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.estado = 1 
					AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.utimestamp != 0");
					
}

// Get monitor WARNING, except disabled and non-init

function groups_monitor_warning ($group_array) {

	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	} else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
			
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
				FROM tagente_estado, tagente, tagente_modulo
				WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
					AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.estado = 2 
					AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.utimestamp != 0");
					
}

// Get monitor UNKNOWN, except disabled and non-init

function groups_monitor_unknown ($group_array) {

	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	} else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
			
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(tagente_estado.id_agente_estado)
				FROM tagente_estado, tagente, tagente_modulo
				WHERE tagente.id_grupo IN $group_clause AND tagente.disabled = 0
					AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.estado = 3 
					AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.utimestamp != 0");
					
}

// Get alerts defined for a given group, except disabled 

function groups_monitor_alerts ($group_array) {

	// If there are not groups to query, we jump to nextone
	
	if (empty ($group_array)) {
		return 0;
		
	} else if (!is_array ($group_array)){
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
		
	} else if (!is_array ($group_array)){
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

// Total agents in a group, except disabled ones

function groups_total_agents ($group_array) {
	
	if (empty ($group_array)) {
		return 0;
		
	} else if (!is_array ($group_array)){
		$group_array = array($group_array);
	}
			
	$group_clause = implode (",", $group_array);
	$group_clause = "(" . $group_clause . ")";
	
	return db_get_sql ("SELECT COUNT(*) FROM tagente WHERE id_grupo IN $group_clause AND disabled = 0");
					
}

?>
