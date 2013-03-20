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

$groups = users_get_groups($id_user, 'ER');

//Group selection
if ($ev_group > 0 && in_array ($ev_group, array_keys ($groups))) {
	
	if ($meta) {
		// In metaconsole the group search is performed by name
		$group_name = groups_get_name ($ev_group);
		$sql_post = " AND group_name = '$group_name'";
	}
	else {
		//If a group is selected and it's in the groups allowed
		$sql_post = " AND id_grupo = $ev_group";
	}
}
else {
	if (is_user_admin ($id_user)) {
		//Do nothing if you're admin, you get full access
		$sql_post = "";
	}
	else {
		//Otherwise select all groups the user has rights to.
		$sql_post = " AND id_grupo IN (" .
			implode (",", array_keys ($groups)) . ")";
	}
}

// Skip system messages if user is not PM
if (!check_acl ($id_user, 0, "PM")) {
	$sql_post .= " AND id_grupo != 0";
}

switch ($status) {
	case 0:
	case 1:
	case 2:
		$sql_post .= " AND estado = " . $status;
		break;
	case 3:
		$sql_post .= " AND (estado = 0 OR estado = 2)";
		break;
}

if ($search != "") {
	$sql_post .= " AND evento LIKE '%" . io_safe_input($search) . "%'";
}

if ($event_type != "") {
	// If normal, warning, could be several (going_up_warning, going_down_warning... too complex 
	// for the user so for him is presented only "warning, critical and normal"
	if ($event_type == "warning" || $event_type == "critical"
		|| $event_type == "normal") {
		$sql_post .= " AND event_type LIKE '%$event_type%' ";
	}
	elseif ($event_type == "not_normal") {
		$sql_post .= " AND event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ";
	}
	elseif ($event_type != "all") {
		$sql_post .= " AND event_type = '" . $event_type."'";
	}
	
}

if ($severity != -1) {
	switch ($severity) {
		case EVENT_CRIT_WARNING_OR_CRITICAL:
			$sql_post .= " AND (criticity = " . EVENT_CRIT_WARNING . " OR 
								criticity = " . EVENT_CRIT_CRITICAL . ")";
			break;
		case EVENT_CRIT_NOT_NORMAL:
			$sql_post .= " AND criticity != " . EVENT_CRIT_NORMAL;
			break;
		default:
			$sql_post .= " AND criticity = $severity";
			break;
	}
}

// In metaconsole mode the agent search is performed by name
if ($meta) {
	if ($text_agent != __('All')) {
		$sql_post .= " AND agent_name LIKE '%$text_agent%'";
	}
}
else {
	switch ($id_agent) {
		case 0:
			break;
		case -1:
			// Agent doesnt exist. No results will returned
			$sql_post .= " AND 1 = 0";
			break;
		default:
			$sql_post .= " AND id_agente = " . $id_agent;
			break;
	}
}

if ($id_user_ack != "0")
	$sql_post .= " AND id_usuario = '" . $id_user_ack . "'";


if ($event_view_hr > 0) {
	$unixtime = get_system_time () - ($event_view_hr * SECONDS_1HOUR);
	$sql_post .= " AND (utimestamp > " . $unixtime . ")";
}

//Search by tag
if (!empty($tag_with)) {
	$sql_post .= ' AND ( ';
	$first = true;
	foreach ($tag_with as $id_tag) {
		if ($first) $first = false;
		else $sql_post .= " OR ";
		$sql_post .= "tags LIKE '%" . tags_get_name($id_tag) . "%'";
	}
	$sql_post .= ' ) ';
}
if (!empty($tag_without)) {
	$sql_post .= ' AND ( ';
	$first = true;
	foreach ($tag_without as $id_tag) {
		if ($first) $first = false;
		else $sql_post .= " AND ";
		
		$sql_post .= "tags NOT LIKE '%" . tags_get_name($id_tag) . "%'";
	}
	$sql_post .= ' ) ';
}

// Filter/Only alerts
if (isset($filter_only_alert)) {
	if ($filter_only_alert == 0)
		$sql_post .= " AND event_type NOT LIKE '%alert%'";
	else if ($filter_only_alert == 1)
		$sql_post .= " AND event_type LIKE '%alert%'";
}

// Tags ACLS
if ($ev_group > 0 && in_array ($ev_group, array_keys ($groups))) {
	$group_array = (array) $ev_group;
}
else {
	$group_array = array_keys($groups);
}

$tags_acls_condition = tags_get_acl_tags($id_user, $group_array, 'ER', 'event_condition', 'AND');

$sql_post .= $tags_acls_condition;

// Metaconsole fitlers
if ($meta) {
	$enabled_nodes = db_get_all_rows_sql('SELECT id FROM tmetaconsole_setup WHERE disabled = 0');
	
	if (empty($enabled_nodes)) {
		$sql_post .= ' AND 1 = 0';
	}
	else {
		$enabled_nodes_id = array();
		foreach ($enabled_nodes as $en) {
			$enabled_nodes_id[] = $en['id'];
		}
		$sql_post .= ' AND server_id IN ('.implode(',',$enabled_nodes_id).')';
	}
}

?>
