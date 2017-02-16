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

if (check_acl ($id_user, 0, "ER"))
	$groups = users_get_groups($id_user, 'ER');
elseif (check_acl ($id_user, 0, "EW"))
	$groups = users_get_groups($id_user, 'EW');
elseif (check_acl ($id_user, 0, "EM"))
	$groups = users_get_groups($id_user, 'EM');


$propagate = db_get_value('propagate','tgrupo','id_grupo',$id_group);

if ($id_group > 0) {
	if ($propagate) {
		$childrens_ids = array($id_group);
		
		$childrens = groups_get_childrens($id_group);
		
		if (!empty($childrens)) {
			foreach ($childrens as $child) {
				$childrens_ids[] = (int)$child['id_grupo'];
			}
		}
	}
	else {
		$childrens_ids = array();
	}
}
else {
	$childrens_ids = array_keys($groups);
}

//Group selection
if ($id_group > 0 && in_array ($id_group, array_keys ($groups))) {
	if ($propagate) {
		$sql_post = " AND id_grupo IN (" . implode(',', $childrens_ids) . ")";
	}
	else {
		//If a group is selected and it's in the groups allowed
		$sql_post = " AND id_grupo = $id_group";
	}
}
else {
	$sql_post = " AND id_grupo IN (" . implode (",", array_keys ($groups)) . ")";
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
	$sql_post .= " AND (evento LIKE '%". io_safe_input($search) . "%' OR id_evento LIKE '%$search%')";
}

if ($event_type != "") {
	// If normal, warning, could be several (going_up_warning, going_down_warning... too complex 
	// for the user so for him is presented only "warning, critical and normal"
	if ($event_type == "warning" || $event_type == "critical" || $event_type == "normal") {
		$sql_post .= " AND event_type LIKE '%$event_type%' ";
	}
	else if ($event_type == "not_normal") {
		$sql_post .= " AND (event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%') ";
	}
	else if ($event_type != "all") {
		$sql_post .= " AND event_type = '" . $event_type."'";
	}
	
}

if ($severity != -1) {
	switch ($severity) {
		case EVENT_CRIT_WARNING_OR_CRITICAL:
			$sql_post .= "
				AND (criticity = " . EVENT_CRIT_WARNING . " OR 
					criticity = " . EVENT_CRIT_CRITICAL . ")";
			break;
		case EVENT_CRIT_OR_NORMAL:
			$sql_post .= "
				AND (criticity = " . EVENT_CRIT_NORMAL . " OR 
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



if ($meta) {
	//There is another filter.
}
else {
	if (!empty($text_module)) {
		$sql_post .= " AND id_agentmodule IN (
				SELECT id_agente_modulo
				FROM tagente_modulo
				WHERE nombre = '$text_module'
			)";
	}
}

if ($id_user_ack != "0")
	$sql_post .= " AND id_usuario = '" . $id_user_ack . "'";

if (!isset($date_from)) {
	$date_from = "";
}
if (!isset($date_to)) {
	$date_to = "";
}

if (($date_from == '') && ($date_to == '')) {
	if ($event_view_hr > 0) {
		$unixtime = get_system_time () - ($event_view_hr * SECONDS_1HOUR);
		$sql_post .= " AND (utimestamp > " . $unixtime . ")";
	}
}
else {
	if ($date_from != '') {
		if($time_from != '') {
			$udate_from = strtotime($date_from . " " . $time_from);
			$sql_post .= " AND (utimestamp >= " . $udate_from . ")";
		} else {
			$udate_from = strtotime($date_from . " 00:00:00");
			$sql_post .= " AND (utimestamp >= " . $udate_from . ")";
		}
	}
	if ($date_to != '') {
		if($time_to != '') {
			$udate_to = strtotime($date_to . " " . $time_to);
			$sql_post .= " AND (utimestamp <= " . $udate_to . ")";
		} else {
			$udate_to = strtotime($date_to . " 23:59:59");
			$sql_post .= " AND (utimestamp <= " . $udate_to . ")";
		}
	}
}

//Search by tag
if (!empty($tag_with)) {
	$sql_post .= ' AND ( ';
	$first = true;
	foreach ($tag_with as $id_tag) {
		if ($first) $first = false;
		else $sql_post .= " AND ";
		$sql_post .= "tags LIKE '" . tags_get_name($id_tag) . "'";
		$sql_post .= " OR ";
		$sql_post .= "tags LIKE '" . tags_get_name($id_tag) . ",%'";
		$sql_post .= " OR ";
		$sql_post .= "tags LIKE '%, " . tags_get_name($id_tag) . "'";
		$sql_post .= " OR ";
		$sql_post .= "tags LIKE '%, " . tags_get_name($id_tag) . ",%'";
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
if ($id_group > 0 && in_array ($id_group, array_keys ($groups))) {
	$group_array = (array) $id_group;
}
else {
	$group_array = array_keys($groups);
}
if (check_acl ($id_user, 0, "ER"))
	$tags_acls_condition = tags_get_acl_tags($id_user, $group_array, 'ER',
		'event_condition', 'AND', '', $meta, array(), true); //FORCE CHECK SQL "(TAG = tag1 AND id_grupo = 1)"
elseif (check_acl ($id_user, 0, "EW"))
	$tags_acls_condition = tags_get_acl_tags($id_user, $group_array, 'EW',
		'event_condition', 'AND', '', $meta, array(), true); //FORCE CHECK SQL "(TAG = tag1 AND id_grupo = 1)"
elseif (check_acl ($id_user, 0, "EM"))
	$tags_acls_condition = tags_get_acl_tags($id_user, $group_array, 'EM',
		'event_condition', 'AND', '', $meta, array(), true); //FORCE CHECK SQL "(TAG = tag1 AND id_grupo = 1)"

if (($tags_acls_condition != ERR_WRONG_PARAMETERS) && ($tags_acls_condition != ERR_ACL)&& ($tags_acls_condition != -110000)) {
	$sql_post .= $tags_acls_condition;
}

// Metaconsole fitlers
if ($meta) {
	
	if ($server_id) {
		$sql_post .= " AND server_id = " . $server_id;
	} else {
		$enabled_nodes = db_get_all_rows_sql('
			SELECT id
			FROM tmetaconsole_setup
			WHERE disabled = 0');
		
		if (empty($enabled_nodes)) {
			$sql_post .= ' AND 1 = 0';
		}
		else {
			if ($strict_user == 1) {
				$enabled_nodes_id = array();
			} else {
				$enabled_nodes_id = array(0);
			}
			foreach ($enabled_nodes as $en) {
				$enabled_nodes_id[] = $en['id'];
			}
			$sql_post .= ' AND server_id IN (' .
				implode(',',$enabled_nodes_id) . ')';
		}
	}
}
?>
