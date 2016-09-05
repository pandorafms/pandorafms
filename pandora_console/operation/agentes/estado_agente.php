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

ob_start();

require_once ("include/functions_reporting.php");
require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . '/include/functions_users.php');
require_once($config['homedir'] . '/include/functions_modules.php');
enterprise_include_once('include/functions_config_agents.php');

check_login ();

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation", "Trying to access agent main list view");
	require ("general/noaccess.php");
	
	return;
}

if (is_ajax ()) {
	ob_get_clean();
	
	$get_agent_module_last_value = (bool) get_parameter ('get_agent_module_last_value');
	$get_actions_alert_template = (bool) get_parameter("get_actions_alert_template");
	
	if ($get_actions_alert_template) {
		$id_template = get_parameter("id_template");
		
		$own_info = get_user_info ($config['id_user']);
		$usr_groups = array();
		$usr_groups = users_get_groups($config['id_user'], 'LW', true);
		
		$filter_groups = '';
		$filter_groups = implode(',', array_keys($usr_groups));
		
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = sprintf ("SELECT t1.id, t1.name,
						(SELECT COUNT(t2.id) 
							FROM talert_templates t2 
							WHERE t2.id =  %d 
								AND t2.id_alert_action = t1.id) as 'sort_order'
					FROM talert_actions t1
					WHERE id_group IN (%s) 
					ORDER BY sort_order DESC", $id_template, $filter_groups);
				break;
			case "oracle":
				$sql = sprintf ("SELECT t1.id, t1.name,
						(SELECT COUNT(t2.id) 
							FROM talert_templates t2 
							WHERE t2.id =  %d 
								AND t2.id_alert_action = t1.id) as sort_order
					FROM talert_actions t1
					WHERE id_group IN (%s) 
					ORDER BY sort_order DESC", $id_template, $filter_groups);
				break;
			case "postgresql":
				$sql = sprintf ("SELECT t1.id, t1.name,
						(SELECT COUNT(t2.id) 
							FROM talert_templates t2 
							WHERE t2.id =  %d 
								AND t2.id_alert_action = t1.id) as sort_order
					FROM talert_actions t1
					WHERE id_group IN (%s) 
					ORDER BY sort_order DESC", $id_template, $filter_groups);
				break;
		}
		
		$rows = db_get_all_rows_sql($sql);
		
		
		if ($rows !== false)
			echo json_encode($rows);
		else
			echo "false";
		
		return;
	}
	
	if ($get_agent_module_last_value) {
		$id_module = (int) get_parameter ('id_agent_module');
		
		if (! check_acl ($config['id_user'], agents_get_agentmodule_group ($id_module), "AR")) {
			db_pandora_audit("ACL Violation",
				"Trying to access agent main list view");
			echo json_encode (false);
			return;
		}
		echo json_encode (modules_get_last_value ($id_module));
		return;
	}
	
	return;
}
ob_end_clean();


// Take some parameters (GET)
$group_id = (int) get_parameter ("group_id", 0);
$search = trim(get_parameter ("search", ""));
$offset = (int)get_parameter('offset', 0);
$refr = get_parameter('refr', 0);
$recursion = get_parameter('recursion', 0);
$status = (int) get_parameter ('status', -1);

$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

$onheader = array();

if (check_acl ($config['id_user'], 0, "AW")) {
	// Prepare the tab system to the future
	$tab = 'setup';
	
	/* Setup tab */
	$setuptab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente">' 
		. html_print_image ("images/setup.png", true, array ("title" =>__('Setup')))
		. '</a>';
	
	$setuptab['godmode'] = true;
	
	$setuptab['active'] = false;
	
	$onheader = array('setup' => $setuptab);
}

ui_print_page_header ( __("Agent detail"), "images/agent_mc.png", false, "agent_status", false, $onheader);

if (!$strict_user) {
	if (tags_has_user_acl_tags()) {
		ui_print_tags_warning();
	}
}

// User is deleting agent
if (isset($result_delete)) {
	if ($result_delete)
		ui_print_success_message(__("Sucessfully deleted agent"));
	else
		ui_print_error_message(__("There was an error message deleting the agent"));
}

echo '<form method="post" action="?sec=estado&sec2=operation/agentes/estado_agente&group_id=' . $group_id . '">';

echo '<table cellpadding="4" cellspacing="4" class="databox filters" width="100%" style="font-weight: bold; margin-bottom: 10px;">';

echo '<tr><td style="white-space:nowrap;">';

echo __('Group') . '&nbsp;';

$groups = users_get_groups ();
html_print_select_groups(false, "AR", true, 'group_id', $group_id, 'this.form.submit()', '', '', false, false, true, '', false, 'width:150px');

echo '</td><td style="white-space:nowrap;">';

echo __("Recursion") . '&nbsp;';
html_print_checkbox ("recursion", 1, $recursion, false, false, 'this.form.submit()');

echo '</td><td style="white-space:nowrap;">';

echo __('Search') . '&nbsp;';
html_print_input_text ("search", $search, '', 12);

echo '</td><td style="white-space:nowrap;">';

$fields = array ();
$fields[AGENT_STATUS_NORMAL] = __('Normal');
$fields[AGENT_STATUS_WARNING] = __('Warning');
$fields[AGENT_STATUS_CRITICAL] = __('Critical');
$fields[AGENT_STATUS_UNKNOWN] = __('Unknown');
$fields[AGENT_STATUS_NOT_NORMAL] = __('Not normal');
$fields[AGENT_STATUS_NOT_INIT] = __('Not init');

echo __('Status') . '&nbsp;';
html_print_select ($fields, "status", $status, 'this.form.submit()', __('All'), AGENT_STATUS_ALL, false, false, true, '', false, 'width: 90px;');

echo '</td><td style="white-space:nowrap;">';

html_print_submit_button (__('Search'), "srcbutton", '',
	array ("class" => "sub search"));

echo '</td><td style="width:5%;">&nbsp;</td>';

echo '</tr></table></form>';

if ($search != "") {
	$filter = array ("string" => '%' . $search . '%');
}
else {
	$filter = array ();
}

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');

$selected = 'border: 1px solid black;';
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
$order = null;


$order_collation = "";
switch ($config["dbtype"]) {
	case "mysql":
		$order_collation = " COLLATE utf8_general_ci";
		break;
	case "postgresql":
	case "oracle":
		$order_collation = "";
		break;
}


switch ($sortField) {
	case 'name':
		switch ($sort) {
			case 'up':
				$selectNameUp = $selected;
				$order = array('field' => 'nombre' . $order_collation,
					'field2' => 'nombre' . $order_collation, 'order' => 'ASC');
				break;
			case 'down':
				$selectNameDown = $selected;
				$order = array('field' => 'nombre' . $order_collation,
					'field2' => 'nombre' . $order_collation, 'order' => 'DESC');
				break;
		}
		break;
	case 'os':
		switch ($sort) {
			case 'up':
				$selectOsUp = $selected;
				$order = array('field' => 'id_os',
					'field2' => 'nombre' . $order_collation, 'order' => 'ASC');
				break;
			case 'down':
				$selectOsDown = $selected;
				$order = array('field' => 'id_os',
					'field2' => 'nombre' . $order_collation, 'order' => 'DESC');
				break;
		}
		break;
	case 'interval':
		switch ($sort) {
			case 'up':
				$selectIntervalUp = $selected;
				$order = array('field' => 'intervalo',
					'field2' => 'nombre' . $order_collation, 'order' => 'ASC');
				break;
			case 'down':
				$selectIntervalDown = $selected;
				$order = array('field' => 'intervalo',
					'field2' => 'nombre' . $order_collation, 'order' => 'DESC');
				break;
		}
		break;
	case 'group':
		switch ($sort) {
			case 'up':
				$selectGroupUp = $selected;
				$order = array('field' => 'id_grupo',
					'field2' => 'nombre' . $order_collation, 'order' => 'ASC');
				break;
			case 'down':
				$selectGroupDown = $selected;
				$order = array('field' => 'id_grupo',
					'field2' => 'nombre' . $order_collation, 'order' => 'DESC');
				break;
		}
		break;
	case 'last_contact':
		switch ($sort) {
			case 'up':
				$selectLastContactUp = $selected;
				$order = array('field' => 'ultimo_contacto',
					'field2' => 'nombre' . $order_collation, 'order' => 'DESC');
				break;
			case 'down':
				$selectLastContactDown = $selected;
				$order = array('field' => 'ultimo_contacto',
					'field2' => 'nombre' . $order_collation, 'order' => 'ASC');
				break;
		}
		break;
	case 'description':
		switch ($sort) {
			case 'up':
				$selectLastContactUp = $selected;
				$order = array('field' => 'comentarios',
					'field2' => 'nombre' . $order_collation, 'order' => 'DESC');
				break;
			case 'down':
				$selectLastContactDown = $selected;
				$order = array('field' => 'comentarios',
					'field2' => 'nombre' . $order_collation, 'order' => 'ASC');
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
		$order = array('field' => 'nombre' . $order_collation,
			'field2' => 'nombre' . $order_collation,
			'order' => 'ASC');
		break;
}

$search_sql = '';
if ($search != "") {
	//$search_sql = " AND ( nombre " . $order_collation . " LIKE '%$search%' OR direccion LIKE '%$search%' OR comentarios LIKE '%$search%') ";
	$sql = "SELECT DISTINCT taddress_agent.id_agent FROM taddress
	INNER JOIN taddress_agent ON
	taddress.id_a = taddress_agent.id_a
	WHERE taddress.ip LIKE '%$search%'";

	$id = db_get_all_rows_sql($sql);
	if($id != ''){
		$aux = $id[0]['id_agent'];
		$search_sql = " AND ( nombre " . $order_collation . "
			LIKE '%$search%' OR tagente.id_agente = $aux";
		if(count($id)>=2){
			for ($i = 1; $i < count($id); $i++){
				$aux = $id[$i]['id_agent'];
				$search_sql .= " OR tagente.id_agente = $aux";
			}
		}
		$search_sql .= ")";
	}else{
		$search_sql = " AND ( nombre " . $order_collation . "
			LIKE '%$search%') ";
	}
}

// Show only selected groups
if ($group_id > 0) {
	$groups = array($group_id);
	if ($recursion) {
		$groups = groups_get_id_recursive($group_id, true);
	}
}
else {
	$groups = array();
	$user_groups = users_get_groups($config["id_user"], "AR");
	$groups = array_keys($user_groups);
}


if ($strict_user) {
	
	$count_filter = array (
		'order' => 'tagente.nombre COLLATE utf8_general_ci ASC',
		'disabled' => 0,
		'status' => $status,
		'search' => $search);
	$filter = array (
		'order' => 'tagente.nombre COLLATE utf8_general_ci ASC',
		'disabled' => 0,
		'status' => $status,
		'search' => $search,
		'offset' => (int) get_parameter ('offset'),
		'limit' => (int) $config['block_size']);
		
	if ($group_id > 0) {
		$groups = array($group_id);
		if ($recursion) {
			$groups = groups_get_id_recursive($group_id, true);
		}
		$filter['id_group'] = implode(',', $groups);
		$count_filter['id_group'] = $filter['id_group'];
	}
	
	$fields = array ('tagente.id_agente','tagente.id_grupo','tagente.id_os','tagente.ultimo_contacto','tagente.intervalo','tagente.comentarios description','tagente.quiet',
		'tagente.normal_count','tagente.warning_count','tagente.critical_count','tagente.unknown_count','tagente.notinit_count','tagente.total_count','tagente.fired_count');
	
	$acltags = tags_get_user_module_and_tags ($config['id_user'], 'AR', $strict_user);
	
	$total_agents = tags_get_all_user_agents (false, $config['id_user'], $acltags, $count_filter, $fields, false, $strict_user, true);
	$total_agents = count($total_agents);
	$agents = tags_get_all_user_agents (false, $config['id_user'], $acltags, $filter, $fields, false, $strict_user, true);
	
	
}
else {
	$total_agents = agents_get_agents(array (
		'disabled' => 0,
		'id_grupo' => $groups,
		'search' => $search_sql,
		'status' => $status),
		array ('COUNT(*) as total'), 'AR', false);
	$total_agents = isset ($total_agents[0]['total']) ?
		$total_agents[0]['total'] : 0;
	
	$agents = agents_get_agents(array (
		'order' => 'nombre ' . $order_collation . ' ASC',
		'id_grupo' => $groups,
		'disabled' => 0,
		'status' => $status,
		'search' => $search_sql,
		'offset' => (int) get_parameter ('offset'),
		'limit' => (int) $config['block_size']),
		
		array ('id_agente',
			'id_grupo',
			'id_os',
			'ultimo_contacto',
			'intervalo',
			'comentarios description',
			'quiet',
			'normal_count',
			'warning_count',
			'critical_count',
			'unknown_count',
			'notinit_count',
			'total_count',
			'fired_count'),
		'AR',
		$order);
}

if (empty ($agents)) {
	$agents = array ();
}

// Prepare pagination
ui_pagination ($total_agents,
ui_get_url_refresh (array ('group_id' => $group_id, 'recursion' => $recursion, 'search' => $search, 'sort_field' => $sortField, 'sort' => $sort, 'status' => $status)));

// Show data.
$table = new stdClass();
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->width = "100%";
$table->class = "databox data";

$table->head = array ();
$table->head[0] = __('Agent'). ' ' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=name&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=name&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';
$table->size[0] = "10%";

$table->head[1] = __('Description'). ' ' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=description&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=description&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';

$table->size[1] = "25%";

$table->head[2] = __('OS'). ' ' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=os&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectOsUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=os&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectOsDown, "alt" => "down")) . '</a>';
$table->size[2] = "8%";

$table->head[3] = __('Interval'). ' ' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=interval&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectIntervalUp, "alt" => "up")) . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=interval&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectIntervalDown, "alt" => "down")) . '</a>';
$table->size[3] = "10%";

$table->head[4] = __('Group'). ' ' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=group&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectGroupUp, "alt" => "up")) . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=group&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectGroupDown, "alt" => "down")) . '</a>';
$table->size[4] = "15%";

$table->head[5] = __('Modules');
$table->size[5] = "10%";

$table->head[6] = __('Status');
$table->size[6] = "4%";

$table->head[7] = __('Alerts');
$table->size[7] = "4%";

$table->head[8] = __('Last contact'). ' ' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=last_contact&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectLastContactUp, "alt" => "up")) . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=last_contact&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectLastContactDown, "alt" => "down")) . '</a>';
$table->size[8] = "15%";

$table->align = array ();

$table->align[2] = "left";
$table->align[3] = "left";
$table->align[4] = "left";
$table->align[5] = "left";
$table->align[6] = "left";
$table->align[7] = "left";
$table->align[8] = "left";

$table->style = array();
//$table->style[0] = 'width: 15%';

$table->data = array ();

$rowPair = true;
$iterator = 0;
foreach ($agents as $agent) {
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;
	
	$alert_img = agents_tree_view_alert_img ($agent["fired_count"]);
	
	$status_img = agents_tree_view_status_img ($agent["critical_count"],
		$agent["warning_count"], $agent["unknown_count"], $agent["total_count"], 
		$agent["notinit_count"]);
	
	$data = array ();
	
	$data[0] = '<div class="left_' . $agent["id_agente"] . '">';
	$data[0] .= '<span>';
	if ($agent['quiet']) {
		$data[0] .= html_print_image("images/dot_green.disabled.png", true, array("border" => '0', "title" => __('Quiet'), "alt" => "")) . "&nbsp;";
	}
	$data[0] .= ui_print_agent_name($agent["id_agente"], true, 60, 'font-size:6.5pt !important;', true);
	$data[0] .= '</span>';
	$data[0] .= '<div class="agentleft_' . $agent["id_agente"] . '" style="visibility: hidden; clear: left;">';
	$data[0] .= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent["id_agente"].'">'.__('View').'</a>';
	if (check_acl ($config['id_user'], $agent["id_grupo"], "AW")) {
		$data[0] .= ' | ';
		$data[0] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$agent["id_agente"].'">'.__('Edit').'</a>';
	}
	$data[0] .= '</div></div>';
	
	$data[1] = ui_print_truncate_text($agent["description"], 'description', false, true, true, '[&hellip;]', 'font-size: 6.5pt');
	
	$data[2] = ui_print_os_icon ($agent["id_os"], false, true);
	
	$data[3] = human_time_description_raw($agent["intervalo"]);
	
	$data[4] = ui_print_group_icon ($agent["id_grupo"], true);
	
	$data[5] = reporting_tiny_stats($agent, true, 'agent', ':', $strict_user);
	
	
	$data[6] = $status_img;
	
	$data[7] = $alert_img;
	
	
	$last_time = strtotime ($agent["ultimo_contacto"]);
	$now = time ();
	$diferencia = $now - $last_time;
	$time = ui_print_timestamp ($last_time, true, array('style' => 'font-size:6.5pt'));
	$style = '';
	if ($diferencia > ($agent["intervalo"] * 2))
		$data[8] = '<b><span style="color: #ff0000;">'.$time.'</span></b>';
	else
		$data[8] = $time;
	
	// This old code was returning "never" on agents without modules, BAD !!
	// And does not print outdated agents in red. WRONG !!!!
	// $data[7] = ui_print_timestamp ($agent_info["last_contact"], true);
	
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	html_print_table ($table);
	if (check_acl ($config['id_user'], 0, "AW") || check_acl ($config['id_user'], 0, "AM")) {
		echo '<div style="text-align: right; float: right;">';
		echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente">';
			html_print_input_hidden ('new_agent', 1);
			html_print_submit_button (__('Create agent'), 'crt', false, 'class="sub next"');
		echo "</form>";
		echo '</div>';
	}
	ui_pagination ($total_agents,
		ui_get_url_refresh(array(
			'group_id' => $group_id,
			'search' => $search,
			'sort_field' => $sortField,
			'sort' => $sort,
			'status' => $status)),
		0, 0, false, 'offset', false);
	unset ($table);
	
}
else {
	ui_print_info_message ( array ( 'no_close' => true, 'message' => __('There are no defined agents') ) );
	echo '<div style="text-align: right; float: right;">';
	echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente">';
		html_print_input_hidden ('new_agent', 1);
		html_print_submit_button (__('Create agent'), 'crt', false, 'class="sub next"');
	echo "</form>";
	echo '</div>';
}
?>

<script type="text/javascript">
$(document).ready (function () {
	$("[class^='left']").mouseenter (function () {
		console.log($(this));
		$(".agent"+$(this)[0].className).css('visibility', '');
	}).mouseleave(function () {
		$(".agent"+$(this)[0].className).css('visibility', 'hidden');
	});
});
</script>
