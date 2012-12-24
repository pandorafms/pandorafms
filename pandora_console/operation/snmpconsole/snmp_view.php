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
$filter_agent = (string) get_parameter ("filter_agent", '');
$filter_oid = (string) get_parameter ("filter_oid", '');
$filter_severity = (int) get_parameter ("filter_severity", -1);
$filter_fired = (int) get_parameter ("filter_fired", -1);
$filter_status = (int) get_parameter ("filter_status", 0);
$search_string = (string) get_parameter ("search_string", '');
$free_search_string = (string) get_parameter ("free_search_string", '');
$pagination = (int) get_parameter ("pagination", $config["block_size"]);
$offset = (int) get_parameter ('offset',0);

$url = "index.php?sec=estado&sec2=operation/snmpconsole/snmp_view&filter_agent=".$filter_agent."&filter_oid=".$filter_oid."&filter_severity=".$filter_severity."&filter_fired=".$filter_fired."&search_string=".$search_string."&free_search_string=".$free_search_string."&pagination=".$pagination."&offset=".$offset;


if ($config["pure"]) {
	$link = '<a target="_top" href="'.$url.'&pure=0&refr=30">' . html_print_image("images/normalscreen.png", true, array("title" => __('Normal screen')))  . '</a>';
}
else {
	// Fullscreen
	$link = '<a target="_top" href="'.$url.'&pure=1&refr=0">' . html_print_image("images/fullscreen.png", true, array("title" => __('Full screen'))) . '</a>';
}

// Header
ui_print_page_header (__("SNMP Console"), "images/computer_error.png", false, "", false, $link);

// OPERATIONS

// Delete SNMP Trap entry Event (only incident management access).
if (isset ($_GET["delete"])){
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

switch ($config["dbtype"]) {
	case "mysql":
		$sql = sprintf ("SELECT * FROM ttrap ORDER BY timestamp DESC LIMIT %d,%d",$offset,$pagination);
		break;
	case "postgresql":
		$sql = sprintf ("SELECT * FROM ttrap ORDER BY timestamp DESC LIMIT %d OFFSET %d", $pagination, $offset);
		break;
	case "oracle":
		$set = array();
		$set['limit'] = $pagination;
		$set['offset'] = $offset;
		$sql = sprintf ("SELECT * FROM ttrap ORDER BY timestamp DESC");
		$sql = oracle_recode_query ($sql, $set);		
		break;
}
$traps = db_get_all_rows_sql ($sql);
// All traps 
$all_traps = db_get_all_rows_sql ("SELECT * FROM ttrap");

if (($config['dbtype'] == 'oracle') && ($traps !== false)) {
	for ($i=0; $i < count($traps); $i++) {
		unset($traps[$i]['rnum']);		
	}
}

// No traps 
if (empty ($traps)) {
	return;
}

$table->width = '90%';
$table->size = array ();
$table->size[0] = '120px';
$table->data = array ();

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
		$sql = "SELECT * FROM ttrap %s ORDER BY timestamp DESC LIMIT %d,%d";
		break;
	case "postgresql":
		$sql = "SELECT * FROM ttrap %s ORDER BY timestamp DESC LIMIT %d OFFSET %d";
		break;
	case "oracle":
		$sql = "SELECT * FROM ttrap %s ORDER BY timestamp DESC"; 
		break;
}
$whereSubquery = 'WHERE 1=1';

if ($filter_agent != '') {
	switch ($config["dbtype"]) {
		case "mysql":
			$whereSubquery .= ' AND source LIKE "' . $filter_agent . '"';
			break;
		case "postgresql":
		case "oracle":
			$whereSubquery .= ' AND source LIKE \'' . $filter_agent . '\'';
			break;
	}
}

if ($filter_oid != '') {
	//Test if install the enterprise to search oid in text or oid field in ttrap.
	if ($config['enterprise_installed']) {
		switch ($config["dbtype"]) {
			case "mysql":
				$whereSubquery .= ' AND (text LIKE "' . $filter_oid . '" OR oid LIKE "' . $filter_oid . '")';
				break;
			case "postgresql":
			case "oracle":
				$whereSubquery .= ' AND (text LIKE \'' . $filter_oid . '\' OR oid LIKE \'' . $filter_oid . '\')';
				break;
		}
	}
	else {
		switch ($config["dbtype"]) {
			case "mysql":
				$whereSubquery .= ' AND oid LIKE "' . $filter_oid . '"';
				break;
			case "postgresql":
			case "oracle":
				$whereSubquery .= ' AND oid LIKE \'' . $filter_oid . '\'';
				break;
		}
	}
}
if ($filter_fired != -1)
	$whereSubquery .= ' AND alerted = ' . $filter_fired;
if ($search_string != '') {
	switch ($config["dbtype"]) {
		case "mysql":
			$whereSubquery .= ' AND value LIKE "%' . $search_string . '%"';
			break;
		case "postgresql":
		case "oracle":
			$whereSubquery .= ' AND value LIKE \'%' . $search_string . '%\'';
			break;
	}
}
if ($free_search_string != '') {
	switch ($config["dbtype"]) {
		case "mysql":
			$whereSubquery .= ' AND (source LIKE "%' . $free_search_string . '%" OR
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
			$whereSubquery .= ' AND (source LIKE \'%' . $free_search_string . '%\' OR
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

$traps = db_get_all_rows_sql($sql);

if (($config['dbtype'] == 'oracle') && ($traps !== false)) {
	for ($i=0; $i < count($traps); $i++) {
		unset($traps[$i]['rnum']);		
	}
}

// Agent select
$table->data[0][0] = '<strong>'.__('Agent').'</strong>';
$table->data[0][1] = html_print_select ($agents, 'filter_agent', $filter_agent, 'javascript:this.form.submit();', __('All'), '', true);

// OID select
$table->data[0][2] = '<strong>'.__('OID').'</strong>';
$table->data[0][3] = html_print_select ($oids, 'filter_oid', $filter_oid, 'javascript:this.form.submit();', __('All'), '', true);

// Alert status select
$table->data[1][0] = '<strong>'.__('Alert').'</strong>';
$table->data[1][1] = html_print_select ($alerted, "filter_fired", $filter_fired, 'javascript:this.form.submit();', __('All'), '-1', true);

// String search_string
$table->data[1][2] = '<strong>'.__('Search value').'</strong>';
$table->data[1][3] = html_print_input_text ('search_string', $search_string, '', 40, 0, true);

// Block size for pagination select
$table->data[2][0] = '<strong>'.__('Block size for pagination').'</strong>';
$paginations[25] = 25;
$paginations[50] = 50;
$paginations[100] = 100;
$paginations[200] = 200;
$paginations[500] = 500;
$table->data[2][1] = html_print_select ($paginations, "pagination", $pagination, 'this.form.submit();', __('Default'), $config["block_size"], true);

// Severity select
$table->data[2][2] = '<strong>'.__('Severity').'</strong>';
$table->data[2][3] = html_print_select ($severities, 'filter_severity', $filter_severity, 'this.form.submit();', __('All'), -1, true);

// Status
$table->data[3][0] = '<strong>'.__('Status').'</strong>';
$status[-1] = __('All');
$status[0] = __('Not validated');
$status[1] = __('Validated');
$table->data[3][1] = html_print_select ($status, 'filter_status', $filter_status, 'this.form.submit();', '', '', true);

// Free search (search by all alphanumeric fields)
$table->data[3][3] = '<strong>'.__('Free search').'</strong>' . ui_print_help_tip(__('Search by any alphanumeric field in the trap'), true);
$table->data[3][4] = html_print_input_text ('free_search_string', $free_search_string, '', 40, 0, true);

$filter = '<form method="POST" action="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&refr='.$config["refr"].'&pure='.$config["pure"].'">';
$filter .= html_print_table($table, true);
$filter .= '<div style="width: ' . $table->width . '; text-align: right;">';
$filter .= html_print_submit_button(__('Update'), 'search', false, 'class="sub upd"', true);
$filter .= '</div>';
$filter .= '</form>';

ui_toggle($filter, __('Toggle filter(s)'));

unset ($table);

// Prepare index for pagination
$trapcount = db_get_sql ("SELECT COUNT(id_trap) FROM ttrap " . $whereSubquery);

$urlPagination = "index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&filter_agent=" . $filter_agent
	. "&filter_oid=" . $filter_oid . "&filter_severity=" . $filter_severity
	. "&filter_fired=" . $filter_fired . "&filter_status=" . $filter_status
	. "&search_string=" . $search_string . "&pagination=".$pagination."&offset=".$offset."&refr=".$config["refr"]."&pure=".$config["pure"];
ui_pagination ($trapcount, $urlPagination, $offset, $pagination);

echo '<form name="eventtable" method="POST" action="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&pagination='.$pagination.'&offset='.$offset.'">';

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
$table->size[2] = '13%';

$table->head[3] = __('Value');
$table->align[3] = "center";
$table->size[3] = '10%';

$table->head[4] = __('Custom');
$table->align[4] = "center";
$table->size[4] = '12%';

$table->head[5] = __('User ID');
$table->align[5] = "center";
$table->size[5] = '10%';

$table->head[6] = __('Timestamp');
$table->align[6] = "center";
$table->size[6] = '10%';

$table->head[7] = __('Alert');
$table->align[7] = "center";
$table->size[7] = '10%';

$table->head[8] = __('Action');
$table->align[8] = "center";
$table->size[8] = '10%';

$table->head[9] = html_print_checkbox_extended ("allbox", 1, false, false, "javascript:CheckAll();", 'class="chk" title="'.__('All').'"', true);
$table->align[9] = "center";
$table->size[9] = '5%';

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
		} else {
			$data[0] = html_print_image("images/pixel_green.png", true, array("title" => __('Validated'), "width" => "20", "height" => "20"));
		}
	
		// Agent matching source address
		$agent = agents_get_agent_with_ip ($trap['source']);
		if ($agent === false) {
			if (! check_acl ($config["id_user"], 0, "AR")) {
				continue;
			}
			$data[1] = '<a href="index.php?sec=estado&sec2=godmode/agentes/configurar_agente&new_agent=1&direccion='.$trap["source"].'" title="'.__('Create agent').'">'.$trap["source"].'</a>';	
		} else {
			if (! check_acl ($config["id_user"], $agent["id_grupo"], "AR")) {
				continue;
			}
			$data[1] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent["id_agente"].'" title="'.__('View agent details').'">';
			$data[1] .= '<strong>'.$agent["nombre"].'</strong></a>';
		}
	
		//OID
		if (empty ($trap["oid"])) {
			$data[2] = __('N/A');
		}
		else {
			$data[2] = enterprise_hook ('editor_link', array ($trap));
			if ($data[2] === ENTERPRISE_NOT_HOOK) {
				$data[2] = $trap["oid"];
			}
		}
		
		//Value
		if (empty ($trap["value"])) {
			$data[3] = __('N/A');
		}
		else {
			$data[3] = ui_print_truncate_text($trap["value"], GENERIC_SIZE_TEXT, false);
		}
		
		//Custom
		if (empty ($trap["oid_custom"])) {
			$data[4] = __('N/A');
		}
		else {
			$data[4] = ui_print_truncate_text($trap["oid_custom"], GENERIC_SIZE_TEXT, false);
		}
		
		//User
		if (!empty ($trap["status"])) {
			$data[5] = '<a href="index.php?sec=workspace&sec2=operation/users/user_edit&ver='.$trap["id_usuario"].'">'.substr ($trap["id_usuario"], 0, 8).'</a>';
			if (!empty($trap["id_usuario"]))
				$data[5] .= ui_print_help_tip(get_user_fullname($trap["id_usuario"]), true);
		}
		else {
			$data[5] = '--';
		}
		
		// Timestamp
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
		
		// Severity
		$table->rowclass[$idx] = get_priority_class ($severity);
		
		//Actions
		$data[8] = "";
		
		if (empty ($trap["status"]) && check_acl ($config["id_user"], 0, "IW")) {
			$data[8] .= '<a href="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&check='.$trap["id_trap"].'">' . html_print_image("images/ok.png", true, array("border" => '0', "title" => __('Validate'))) . '</a> ';
		}
		if (check_acl ($config["id_user"], 0, "IM")) {
			$data[8] .= '<a href="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&delete='.$trap["id_trap"].'&offset='.$offset.'" onClick="javascript:return confirm(\''.__('Are you sure?').'\')">' . html_print_image("images/cross.png", true, array("border" => "0", "title" => __('Delete'))) . '</a> ';
		}
		$data[8] .= '<a href="javascript: toggleVisibleExtendedInfo(' . $trap["id_trap"] . ');">' . html_print_image("images/eye.png", true, array("alt" => __('Show more'), "title" => __('Show more'))) .'</a>';
		
		$data[9] = html_print_checkbox_extended ("snmptrapid[]", $trap["id_trap"], false, false, '', 'class="chk"', true);
	
		array_push ($table->data, $data);
		
		//Hiden file for description
		$string = '<table style="border:solid 1px #D3D3D3;" width="90%" class="toggle">
			<tr><td align="left" valign="top" width="15%" ><b>' . __('Custom data:') . '</b></td><td align="left" >' . $trap['oid_custom'] . '</td></tr>'
			 . '<tr><td align="left" valign="top">' . '<b>' . __('OID:') . '</td><td align="left"> ' . $trap['oid'] . '</td></tr>';

        if ($trap["description"] != ""){
            $string .= '<tr><td align="left" valign="top">' . '<b>' . __('Description:') . '</td><td align="left">' . $trap['description'] . '</td></tr>';
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
	echo '<div class="nf">'.__('No matching traps found').'</div>';
} else {
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

function toggleDiv (divid){
	if (document.getElementById(divid).style.display == 'none'){
		document.getElementById(divid).style.display = 'block';
	} else {
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
