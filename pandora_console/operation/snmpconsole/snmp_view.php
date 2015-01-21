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



// Load global vars
global $config;
enterprise_include ("operation/snmpconsole/snmp_view.php");
require_once("include/functions_agents.php");
require_once("include/functions_snmp.php");

check_login ();

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access SNMP Console");
	require ("general/noaccess.php");
	exit;
}

// Read parameters
$filter_severity = (int) get_parameter ("filter_severity", -1);
$filter_fired = (int) get_parameter ("filter_fired", -1);
$filter_status = (int) get_parameter ("filter_status", 0);
$free_search_string = (string) get_parameter ("free_search_string", '');
$pagination = (int) get_parameter ("pagination", $config["block_size"]);
$offset = (int) get_parameter ('offset',0);
$trap_type = (int) get_parameter ('trap_type', -1);
$group_by = (int)get_parameter('group_by', 0);
$refr = (int)get_parameter("refr", 0);

$user_groups = users_get_groups ($config['id_user'],"AR", false);
$str_user_groups = '';
$i = 0;
foreach ($user_groups as $id=>$name) {
	if ($i == 0) {
		$str_user_groups .= $id;
	}
	else {
		$str_user_groups .= ','.$id;
	}
	$i++;
}

$url = "index.php?sec=estado&" .
	"sec2=operation/snmpconsole/snmp_view&" .
	"filter_severity=" . $filter_severity . "&" .
	"filter_fired=" . $filter_fired . "&" .
	"free_search_string=" . $free_search_string . "&" .
	"pagination=" . $pagination . "&" .
	"offset=" . $offset . "&" .
	"trap_type=" . $trap_type . "&" .
	"group_by=" .$group_by;

$statistics['text'] = '<a href="index.php?sec=estado&sec2=operation/snmpconsole/snmp_statistics&pure=' . $config["pure"] . '&refr=' . $refr . '">' . 
	html_print_image("images/op_reporting.png", true, array ("title" => __('Statistics'))) .'</a>';
$list['text'] = '<a href="' . $url . '&pure=' . $config["pure"] . '&refr=' . $refr . '">' . 
	html_print_image("images/op_snmp.png", true, array ("title" => __('List'))) .'</a>';
$list['active'] = true;

if ($config["pure"]) {
	$fullscreen['text'] = '<a target="_top" href="'.$url.'&pure=0&refr=' . $refr . '">' . html_print_image("images/normal_screen.png", true, array("title" => __('Normal screen')))  . '</a>';
}
else {
	// Fullscreen
	$fullscreen['text'] = '<a target="_top" href="'.$url.'&pure=1&refr=' . $refr . '">' . html_print_image("images/full_screen.png", true, array("title" => __('Full screen'))) . '</a>';
}

// Header
ui_print_page_header(__("SNMP Console"), "images/op_snmp.png", false,
	"", false, array($fullscreen, $list, $statistics));

// OPERATIONS

// Delete SNMP Trap entry Event (only incident management access).
if (isset ($_GET["delete"])) {
	$id_trap = (int) get_parameter_get ("delete", 0);
	if ($id_trap > 0 && check_acl ($config['id_user'], 0, "IM")) {
		
		$result = db_process_sql_delete('ttrap', array('id_trap' => $id_trap));
		ui_print_result_message ($result,
			__('Successfully deleted'),
			__('Could not be deleted'));
	}
	else {
		db_pandora_audit("ACL Violation",
			"Trying to delete SNMP event ID #".$id_trap);
	}
}

// Check Event (only incident write access).
if (isset ($_GET["check"])) {
	$id_trap = (int) get_parameter_get ("check", 0);
	if (check_acl ($config['id_user'], 0, "IW")) {
		$values = array(
			'status' => 1,
			'id_usuario' => $config["id_user"]);
		$result = db_process_sql_update('ttrap', $values, array('id_trap' => $id_trap));
		
		ui_print_result_message ($result,
			__('Successfully updated'),
			__('Could not be updated'));
	}
	else {
		db_pandora_audit("ACL Violation",
			"Trying to checkout SNMP Trap ID".$id_trap);
	}
}

// Mass-process DELETE
if (isset ($_POST["deletebt"])) {
	$trap_ids = get_parameter_post ("snmptrapid", array ());
	if (is_array ($trap_ids) && check_acl ($config['id_user'], 0, "IW")) {
		foreach ($trap_ids as $id_trap) {
			db_process_sql_delete('ttrap', array('id_trap' => $id_trap));
		}
	}
	else {
		db_pandora_audit("ACL Violation",
			"Trying to mass-delete SNMP Trap ID");
	}
}

// Mass-process UPDATE
if (isset ($_POST["updatebt"])) {
	$trap_ids = get_parameter_post ("snmptrapid", array ());
	if (is_array ($trap_ids) && check_acl ($config['id_user'], 0, "IW")) {
		foreach ($trap_ids as $id_trap) {
			$sql = sprintf ("UPDATE ttrap SET status = 1, id_usuario = '%s' WHERE id_trap = %d", $config["id_user"], $id_trap);
			db_process_sql ($sql);
		}
	}
	else {
		db_pandora_audit("ACL Violation",
			"Trying to mass-delete SNMP Trap ID");
	}
}

// All traps 
$all_traps = db_get_all_rows_sql ("SELECT DISTINCT source FROM ttrap");
if (empty($all_traps))
	$all_traps = array();

// Set filters
$agents = array ();
$oids = array ();
$severities = get_priorities ();
$alerted = array (__('Not fired'), __('Fired'));
foreach ($all_traps as $trap) {
	$agent = agents_get_agent_with_ip ($trap['source']);
	$agents[$trap["source"]] = $agent !== false ? $agent["nombre"] : $trap["source"];
	$oid = enterprise_hook ('get_oid', array ($trap));
	if ($oid === ENTERPRISE_NOT_HOOK) {
		$oid = $trap["oid"];
	}
	$oids[$oid] = $oid;
}

//Make query to extract traps of DB.
switch ($config["dbtype"]) {
	case "mysql":
		$sql = "SELECT *
			FROM ttrap
			WHERE (`source` IN (
					SELECT direccion FROM tagente
					WHERE id_grupo IN ($str_user_groups)
					) OR `source`='' OR `source` NOT IN (SELECT direccion FROM tagente)) %s
			ORDER BY timestamp DESC
			LIMIT %d,%d";
		break;
	case "postgresql":
	case "oracle":
		$sql = "SELECT *
			FROM ttrap
			WHERE (source IN (
					SELECT direccion FROM tagente
					WHERE id_grupo IN ($str_user_groups)
					) OR source='' OR source NOT IN (SELECT direccion FROM tagente)) %s
			ORDER BY timestamp DESC
			LIMIT %d OFFSET %d";
		break;
}
$sql_all = "SELECT *
	FROM ttrap
	WHERE (source IN (
			SELECT direccion FROM tagente
			WHERE id_grupo IN ($str_user_groups)
			)
	OR source='' OR source NOT IN (SELECT direccion FROM tagente)) %s
	ORDER BY timestamp DESC";
$sql_count = "SELECT COUNT(id_trap)
	FROM ttrap
	WHERE (source IN (
			SELECT direccion FROM tagente
			WHERE id_grupo IN ($str_user_groups)
			)
	OR source='' OR source NOT IN (SELECT direccion FROM tagente)) %s";
//$whereSubquery = 'WHERE 1=1';
$whereSubquery = '';

if ($filter_fired != -1)
	$whereSubquery .= ' AND alerted = ' . $filter_fired;

if ($free_search_string != '') {
	switch ($config["dbtype"]) {
		case "mysql":
			$whereSubquery .= '
				AND (source LIKE "%' . $free_search_string . '%" OR
				oid LIKE "%' . $free_search_string . '%" OR
				oid_custom LIKE "%' . $free_search_string . '%" OR
				type_custom LIKE "%' . $free_search_string . '%" OR
				value LIKE "%' . $free_search_string . '%" OR
				value_custom LIKE "%' . $free_search_string . '%" OR
				id_usuario LIKE "%' . $free_search_string . '%" OR
				text LIKE "%' . $free_search_string . '%" OR
				description LIKE "%' . $free_search_string . '%")';
			break;
		case "postgresql":
		case "oracle":
			$whereSubquery .= '
				AND (source LIKE \'%' . $free_search_string . '%\' OR
				oid LIKE \'%' . $free_search_string . '%\' OR
				oid_custom LIKE \'%' . $free_search_string . '%\' OR
				type_custom LIKE \'%' . $free_search_string . '%\' OR
				value LIKE \'%' . $free_search_string . '%\' OR
				value_custom LIKE \'%' . $free_search_string . '%\' OR
				id_usuario LIKE \'%' . $free_search_string . '%\' OR
				text LIKE \'%' . $free_search_string . '%\' OR
				description LIKE \'%' . $free_search_string . '%\')';
			break;
	}
}

if ($filter_severity != -1) {
	//Test if install the enterprise to search oid in text or oid field in ttrap.
	if ($config['enterprise_installed'])
		$whereSubquery .= ' AND (
			(alerted = 0 AND severity = ' . $filter_severity . ') OR
			(alerted = 1 AND priority = ' . $filter_severity . '))';
	else
		$whereSubquery .= ' AND (
			(alerted = 0 AND 1 = ' . $filter_severity . ') OR
			(alerted = 1 AND priority = ' . $filter_severity . '))';
}
if ($filter_status != -1)
	$whereSubquery .= ' AND status = ' . $filter_status;
	
if ($trap_type == 5) {
	$whereSubquery .= ' AND type NOT IN (0, 1, 2, 3, 4)';	
}
else if ($trap_type != -1){
	$whereSubquery .= ' AND type = ' . $trap_type;
}

if ($group_by) {
	$where_without_group = $whereSubquery;
	$whereSubquery .= ' GROUP BY source,oid';
}
switch ($config["dbtype"]) {
	case "mysql":
		$sql = sprintf($sql, $whereSubquery, $offset, $pagination);
		break;
	case "postgresql":
		$sql = sprintf($sql, $whereSubquery, $pagination, $offset);
		break;
	case "oracle":
		$set = array();
		$set['limit'] = $pagination;
		$set['offset'] = $offset;
		$sql = oracle_recode_query ($sql, $set);
		break;
}
$sql_all = sprintf($sql_all, $whereSubquery);
$sql_count = sprintf($sql_count, $whereSubquery);

$table->width = '90%';
$table->size = array ();
$table->size[0] = '120px';
$table->data = array ();

// Alert status select
$table->data[1][0] = '<strong>'.__('Alert').'</strong>';
$table->data[1][1] = html_print_select ($alerted, "filter_fired", $filter_fired, 'javascript:this.form.submit();', __('All'), '-1', true);

// Block size for pagination select
$table->data[2][0] = '<strong>'.__('Block size for pagination').'</strong>';
$paginations[25] = 25;
$paginations[50] = 50;
$paginations[100] = 100;
$paginations[200] = 200;
$paginations[500] = 500;
$table->data[2][1] = html_print_select ($paginations, "pagination", $pagination, 'this.form.submit();', __('Default'), $config["block_size"], true);

// Severity select
$table->data[1][2] = '<strong>'.__('Severity').'</strong>';
$table->data[1][3] = html_print_select ($severities, 'filter_severity', $filter_severity, 'this.form.submit();', __('All'), -1, true);

// Status
$table->data[3][0] = '<strong>'.__('Status').'</strong>';

$status_array[-1] = __('All');
$status_array[0] = __('Not validated');
$status_array[1] = __('Validated');
$table->data[3][1] = html_print_select ($status_array, 'filter_status', $filter_status, 'this.form.submit();', '', '', true);

// Free search (search by all alphanumeric fields)
$table->data[2][3] = '<strong>'.__('Free search').'</strong>' . ui_print_help_tip(__('Search by any alphanumeric field in the trap'), true);
$table->data[2][4] = html_print_input_text ('free_search_string', $free_search_string, '', 40, 0, true);

// Type filter (ColdStart, WarmStart, LinkDown, LinkUp, authenticationFailure, Other)
$table->data[4][1] = '<strong>'.__('Type').'</strong>' . ui_print_help_tip(__('Search by trap type'), true);
$trap_types = array(-1 => __('None'), 0 => __('Cold start (0)'), 1 => __('Warm start (1)'), 2 => __('Link down (2)'), 3 => __('Link up (3)'), 4 => __('Authentication failure (4)'), 5 => __('Other'));
$table->data[4][2] = html_print_select ($trap_types, 'trap_type', $trap_type, 'this.form.submit();', '', '', true, false, false);

$table->data[3][3] = '<strong>'.__('Group by OID/IP').'</strong>';
$table->data[3][4] = __('Yes') . '&nbsp;'.
	html_print_radio_button ('group_by', 1, '', $group_by, true) .
	'&nbsp;&nbsp;';
$table->data[3][4] .= __('No') . '&nbsp;' .
	html_print_radio_button ('group_by', 0, '', $group_by, true);

$filter = '<form method="POST" action="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&refr='.((int)get_parameter('refr', 0)).'&pure='.$config["pure"].'&tab='.$tab.'">';
$filter .= html_print_table($table, true);
$filter .= '<div style="width: ' . $table->width . '; text-align: right;">';
$filter .= html_print_submit_button(__('Update'), 'search', false, 'class="sub upd"', true);
$filter .= '</div>';
$filter .= '</form>';

ui_toggle($filter, __('Toggle filter(s)'));

unset ($table);

$traps = db_get_all_rows_sql($sql);
$trapcount = (int) db_get_value_sql($sql_count);

// No traps 
if (empty ($traps)) {
	echo '<div class="nf">'.__('There are no SNMP traps in database').'</div>';
	return;
}

if (($config['dbtype'] == 'oracle') && ($traps !== false)) {
	for ($i=0; $i < count($traps); $i++) {
		unset($traps[$i]['rnum']);
	}
}

$url_snmp = "index.php?" .
	"sec=snmpconsole&" .
	"sec2=operation/snmpconsole/snmp_view&" .
	"filter_severity=" . $filter_severity . "&" .
	"filter_fired=" . $filter_fired . "&" .
	"filter_status=" . $filter_status . "&" .
	"refr=" . ((int)get_parameter('refr', 0)) . "&" .
	"pure=" . $config["pure"] . "&" .
	"group_by=" . $group_by . "&" .
	"free_search_string=" . $free_search_string;

$urlPagination = $url_snmp . "&pagination=" . $pagination . "&offset=" . $offset;

ui_pagination ($trapcount, $urlPagination, $offset, $pagination);

echo '<form name="eventtable" method="POST" action="' . $url_snmp . '">';

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = '99%';
$table->class = "databox";
$table->head = array ();
$table->size = array ();
$table->data = array ();
$table->align = array ();

$table->head[0] = __('Status');
$table->align[0] = "center";
$table->size[0] = '5%';

$table->head[1] = __('SNMP Agent');
$table->align[1] = "center";
$table->size[1] = '15%';

$table->head[2] = __('OID');
$table->align[2] = "center";
$table->size[2] = '18%';

if ($group_by) {
	$table->head[3] = __('Count');
	$table->align[3] = "center";
	$table->size[3] = '5%';
}

$table->head[4] = __('Value');
$table->align[4] = "center";
$table->size[4] = '10%';

$table->head[5] = __('User ID');
$table->align[5] = "center";
$table->size[5] = '10%';

$table->head[6] = __('Timestamp');
$table->align[6] = "center";
$table->size[6] = '10%';

$table->head[7] = __('Alert');
$table->align[7] = "center";
$table->size[7] = '5%';

$table->head[8] = __('Action');
$table->align[8] = "center";
$table->size[8] = '10%';

$table->head[9] = html_print_checkbox_extended ("allbox", 1, false, false, "javascript:CheckAll();", 'class="chk" title="'.__('All').'"', true);
$table->align[9] = "center";
$table->size[9] = '5%';

$table->style[8] = "background: #F3F3F3; color: #111 !important;";

// Skip offset records
$idx = 0;
if ($traps !== false) {

	foreach ($traps as $trap) {
		$data = array ();
		if (empty($trap["description"])){
			$trap["description"]="";
		}
		$severity = enterprise_hook ('get_severity', array ($trap));
		if ($severity === ENTERPRISE_NOT_HOOK) {
			$severity = $trap["alerted"] == 1 ? $trap["priority"] : 1;
		}
		
		//Status
		if ($trap["status"] == 0) {
			$data[0] = html_print_image("images/pixel_red.png", true, array("title" => __('Not validated'), "width" => "20", "height" => "20"));
		}
		else {
			$data[0] = html_print_image("images/pixel_green.png", true, array("title" => __('Validated'), "width" => "20", "height" => "20"));
		}
		
		// Agent matching source address
		$table->cellclass[$idx][1] = get_priority_class ($severity);
		$agent = agents_get_agent_with_ip ($trap['source']);
		if ($agent === false) {
			if (! check_acl ($config["id_user"], 0, "AR")) {
				continue;
			}
			$data[1] = '<a href="index.php?sec=estado&sec2=godmode/agentes/configurar_agente&new_agent=1&direccion='.$trap["source"].'" title="'.__('Create agent').'">'.$trap["source"].'</a>';
		}
		else {
			if (! check_acl ($config["id_user"], $agent["id_grupo"], "AR")) {
				continue;
			}
			$data[1] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent["id_agente"].'" title="'.__('View agent details').'">';
			$data[1] .= '<strong>'.$agent["nombre"].'</strong></a>';
		}
		
		//OID
		$table->cellclass[$idx][2] = get_priority_class ($severity);
		$data[2] = '<a href="javascript: toggleVisibleExtendedInfo(' . $trap["id_trap"] . ');">' . (empty($trap["oid"]) ? __('N/A') : $trap["oid"]) .'</a>';
		
		//Count
		if ($group_by) {
			$sql = "SELECT * FROM ttrap WHERE 1=1 
					$where_without_group
					AND oid='".$trap['oid']."' 
					AND source='".$trap['source']."'";
			$group_traps = db_get_all_rows_sql($sql);
			$count_group_traps = count($group_traps);
			$table->cellclass[$idx][3] = get_priority_class ($severity);
			$data[3] = '<strong>'.$count_group_traps.'</strong></a>';
		}
		//Value
		$table->cellclass[$idx][4] = get_priority_class ($severity);
		if (empty ($trap["value"])) {
			$data[4] = __('N/A');
		}
		else {
			$data[4] = ui_print_truncate_text($trap["value"], GENERIC_SIZE_TEXT, false);
		}
		
		//User
		$table->cellclass[$idx][5] = get_priority_class ($severity);
		if (!empty ($trap["status"])) {
			$data[5] = '<a href="index.php?sec=workspace&sec2=operation/users/user_edit&ver='.$trap["id_usuario"].'">'.substr ($trap["id_usuario"], 0, 8).'</a>';
			if (!empty($trap["id_usuario"]))
				$data[5] .= ui_print_help_tip(get_user_fullname($trap["id_usuario"]), true);
		}
		else {
			$data[5] = '--';
		}
		
		// Timestamp
		$table->cellclass[$idx][6] = get_priority_class ($severity);
		$data[6] = '<span title="'.$trap["timestamp"].'">';
		$data[6] .= ui_print_timestamp ($trap["timestamp"], true);
		$data[6] .= '</span>';
		
		// Use alert severity if fired
		if (!empty ($trap["alerted"])) {
			$data[7] = html_print_image("images/pixel_yellow.png", true, array("width" => "20", "height" => "20", "border" => "0", "title" => __('Alert fired'))); 		
		}
		else {
			$data[7] = html_print_image("images/pixel_gray.png", true, array("width" => "20", "height" => "20", "border" => "0", "title" => __('Alert not fired')));
		}
		
		//Actions
		$data[8] = "";
		
		if (empty ($trap["status"]) && check_acl ($config["id_user"], 0, "IW")) {
			$data[8] .= '<a href="' . $url_snmp . '&check='.$trap["id_trap"].'">' . html_print_image("images/ok.png", true, array("border" => '0', "title" => __('Validate'))) . '</a> ';
		}
		if ($trap['source'] == '') {
			$is_admin = db_get_value('is_admin', 'tusuario', 'id_user',$config['id_user']);
			if ($is_admin) {
				$data[8] .= '<a href="' . $url_snmp . '&delete='.$trap["id_trap"].'&offset='.$offset.'" onClick="javascript:return confirm(\''.__('Are you sure?').'\')">' . html_print_image("images/cross.png", true, array("border" => "0", "title" => __('Delete'))) . '</a> ';
			}
		} else {
			$agent_trap_group = db_get_value('id_grupo', 'tagente', 'nombre', $trap['source']);
			if ((check_acl ($config["id_user"], $agent_trap_group, "AW"))) {
				$data[8] .= '<a href="' . $url_snmp . '&delete='.$trap["id_trap"].'&offset='.$offset.'" onClick="javascript:return confirm(\''.__('Are you sure?').'\')">' . html_print_image("images/cross.png", true, array("border" => "0", "title" => __('Delete'))) . '</a> ';
			}
		}

		$data[8] .= '<a href="javascript: toggleVisibleExtendedInfo(' . $trap["id_trap"] . ');">' . html_print_image("images/eye.png", true, array("alt" => __('Show more'), "title" => __('Show more'))) .'</a>';
                $data[8] .= enterprise_hook ('editor_link', array ($trap));

		
		$data[9] = html_print_checkbox_extended ("snmptrapid[]", $trap["id_trap"], false, false, '', 'class="chk"', true);
		
		array_push ($table->data, $data);
		
		//Hiden file for description
		$string = '<table style="border:solid 1px #D3D3D3;" width="90%" class="toggle">
			<tr>
				<td align="left" valign="top" width="15%" ><b>' . __('Custom data:') . '</b></td>
				<td align="left" >';
		
		if ($group_by) {
			$new_url = "index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&" .
			"filter_severity=" . $filter_severity . "&" .
			"filter_fired=" . $filter_fired . "&" .
			"filter_status=" . $filter_status . "&" .
			"refr=" . ((int)get_parameter('refr', 0)) . "&" .
			"pure=" . $config["pure"] . "&" .
			"group_by=0&" .
			"free_search_string=" . $free_search_string;
			
			$string .= '<a href='.$new_url.'>'.__('See more details').'</a>';
		} else {	
			// Print binding vars separately
			$binding_vars = explode ("\t", $trap['oid_custom']);
			foreach ($binding_vars as $var) {
				$string .= $var . "<br/>";
			}
		}
		
		$string .= '</td>
			</tr>
			<tr>
				<td align="left" valign="top">' . '<b>' . __('OID:') . '</td>
				<td align="left"> ' . $trap['oid'] . '</td>
			</tr>';
		
		if ($trap["description"] != "") {
			$string .= '<tr>
					<td align="left" valign="top">' . '<b>' . __('Description:') . '</td>
					<td align="left">' . $trap['description'] . '</td>
				</tr>';
		}
		
		if ($trap["text"] != "") {
			$string .= '<tr>
					<td align="left" valign="top">' . '<b>' . __('Text:') . '</td>
					<td align="left">' . $trap['text'] . '</td>
				</tr>';
		}
		
		if ($trap["type"] != "") {
			$trap_types = array(-1 => __('None'), 0 => __('Cold start (0)'), 1 => __('Warm start (1)'), 2 => __('Link down (2)'), 3 => __('Link up (3)'), 4 => __('Authentication failure (4)'), 5 => __('Other'));
			
			switch ($trap["type"]) {
				case -1:
					$desc_trap_type = __('None');
					break;
				case 0:
					$desc_trap_type = __('Cold start (0)');
					break;
				case 1:
					$desc_trap_type = __('Warm start (1)');
					break;
				case 2:
					$desc_trap_type = __('Link down (2)');
					break;
				case 3:
					$desc_trap_type = __('Link up (3)');
					break;
				case 4:
					$desc_trap_type = __('Authentication failure (4)');
					break;
				default:
					$desc_trap_type = __('Other');
					break;
			}
			$string .= '<tr><td align="left" valign="top">' . '<b>' . __('Type:') . '</td><td align="left">' . $desc_trap_type . '</td></tr>';
		}
		
		if ($group_by) {
			$sql = "SELECT * FROM ttrap WHERE 1=1 
					$where_without_group
					AND oid='".$trap['oid']."' 
					AND source='".$trap['source']."'";
			$group_traps = db_get_all_rows_sql($sql);
			$count_group_traps = count($group_traps);
			
			$sql = "SELECT timestamp FROM ttrap WHERE 1=1 
					$where_without_group
					AND oid='".$trap['oid']."' 
					AND source='".$trap['source']."'
					ORDER BY `timestamp` DESC";		
			$last_trap = db_get_value_sql($sql);
			
			$sql = "SELECT timestamp FROM ttrap WHERE 1=1
					$where_without_group
					AND oid='".$trap['oid']."' 
					AND source='".$trap['source']."'
					ORDER BY `timestamp` ASC";
			$first_trap = db_get_value_sql($sql);
			
			$string .= '<tr>
					<td align="left" valign="top">' . '<b>' . __('Count:') . '</td>
					<td align="left">' . $count_group_traps . '</td>
				</tr>';
			$string .= '<tr>
					<td align="left" valign="top">' . '<b>' . __('First trap:') . '</td>
					<td align="left">' . $first_trap . '</td>
				</tr>';
			$string .= '<tr>
					<td align="left" valign="top">' . '<b>' . __('Last trap:') . '</td>
					<td align="left">' . $last_trap . '</td>
				</tr>';

		}
		$string .=  '</table>';
		
		$data = array($string); //$data = array($trap['description']);
		$idx++;
		$table->rowclass[$idx] = 'trap_info_' . $trap['id_trap'];
		$table->colspan[$idx][0] = 10;
		$table->rowstyle[$idx] = 'display: none;';
		array_push ($table->data, $data);
		
		$idx++;
	}
}

// No matching traps
if ($idx == 0) {
	echo '<div class="nf">' . __('No matching traps found') . '</div>';
}
else {
	html_print_table ($table);
}

unset ($table);

echo '<div style="width:98%; text-align:right;">';
if (check_acl ($config["id_user"], 0, "IW")) {
	html_print_submit_button (__('Validate'), "updatebt", false, 'class="sub ok"');
}

if (check_acl ($config['id_user'], 0, "IM")) {
	echo "&nbsp;";
	html_print_submit_button (__('Delete'), "deletebt", false, 'class="sub delete" onClick="javascript:return confirm(\''.__('Are you sure?').'\')"');
}
echo "</div></form>";


echo '<div style="float:left; padding-left:30px; line-height: 17px; vertical-align: top; width:120px;">';
echo '<h3>' . __('Status') . '</h3>';
echo html_print_image("images/pixel_green.png", true, array("width" => "20", "height" => "20")) . ' - ' . __('Validated');
echo '<br />';
echo html_print_image("images/pixel_red.png", true, array("width" => "20", "height" => "20")) . ' - ' . __('Not validated');
echo '</div>';
echo '<div style="float:left; padding-left:30px; line-height: 17px; vertical-align: top; width:120px;">';
echo '<h3>' . __('Alert') . '</h3>';
echo html_print_image("images/pixel_yellow.png", true, array("width" => "20", "height" => "20")) . ' - ' .__('Fired');
echo '<br />';
echo html_print_image("images/pixel_gray.png", true, array("width" => "20", "height" => "20")) . ' - ' . __('Not fired');
echo '</div>';
echo '<div style="float:left; padding-left:30px; line-height: 19px; vertical-align: top; width:120px;">';
echo '<h3>' . __('Action') . '</h3>';
echo html_print_image("images/ok.png", true) . ' - ' .__('Validate');
echo '<br />';
echo html_print_image("images/cross.png", true) . ' - ' . __('Delete');
echo '</div>';
echo '<div style="float:left; padding-left:30px; line-height: 17px; vertical-align: top; width:120px;">';
echo '<h3>'.__('Legend').'</h3>';
foreach (get_priorities () as $num => $name) {
	echo '<span class="'.get_priority_class ($num).'">'.$name.'</span>';
	echo '<br />';
}
echo '</div>';
echo '<div style="clear:both;">&nbsp;</div>';


?>

<script language="JavaScript" type="text/javascript">
	<!--
	function CheckAll() {
		for (var i = 0; i < document.eventtable.elements.length; i++) {
			var e = document.eventtable.elements[i];
			if (e.type == 'checkbox' && e.name != 'allbox')
				e.checked = !e.checked;
		}
	}
	
	function toggleDiv (divid) {
		if (document.getElementById(divid).style.display == 'none') {
			document.getElementById(divid).style.display = 'block';
		}
		else {
			document.getElementById(divid).style.display = 'none';
		}
	}
	
	function toggleVisibleExtendedInfo(id_trap) {
		display = $('.trap_info_' + id_trap).css('display');
		
		if (display != 'none') {
			$('.trap_info_' + id_trap).css('display', 'none');
		}
		else {
			$('.trap_info_' + id_trap).css('display', '');
		}
	}
	//-->
</script>
