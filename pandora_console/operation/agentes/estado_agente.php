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
							FROM talert_templates AS t2 
							WHERE t2.id =  %d 
								AND t2.id_alert_action = t1.id) as 'sort_order'
					FROM talert_actions AS t1
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
							FROM talert_templates AS t2 
							WHERE t2.id =  %d 
								AND t2.id_alert_action = t1.id) as sort_order
					FROM talert_actions AS t1
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

$agent_to_delete = get_parameter("borrar_agente");
if (!empty($agent_to_delete)) {
	$id_agente = $agent_to_delete;
	$agent_name = agents_get_name ($id_agente);
	$id_grupo = agents_get_agent_group($id_agente);
	if (check_acl ($config["id_user"], $id_grupo, "AW")==1) {
		$id_agentes[0] = $id_agente;
		$result = agents_delete_agent($id_agentes);
		
		if ($result != false)
			$result_delete = true;
		else
			$result_delete = false;
		
		db_pandora_audit("Agent management", "Delete Agent " . $agent_name);
	}
	else {
		// NO permissions.
		db_pandora_audit("ACL Violation",
			"Trying to delete agent \'$agent_name\'");
		require ("general/noaccess.php");
		exit;
	}
}

$first = true;
while ($row = db_get_all_row_by_steps_sql($first, $result, "SELECT * FROM tgrupo")) {
	$first = false;
}

// Take some parameters (GET)
$group_id = (int) get_parameter ("group_id", 0);
$search = trim(get_parameter ("search", ""));
$offset = get_parameter('offset', 0);
$refr = get_parameter('refr', 0);
$recursion = get_parameter('recursion', 0);
$status = (int) get_parameter ('status', -1);

$onheader = array();

if (check_acl ($config['id_user'], 0, "AW")) {
	// Prepare the tab system to the future
	$tab = 'setup';

	/* Setup tab */
	$setuptab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/modificar_agente">' 
			. html_print_image ("images/setup.png", true, array ("title" =>__('Setup')))
			. '</a>';
			
	if($tab == 'setup')
		$setuptab['active'] = true;
	else
		$setuptab['active'] = false;
		
	$onheader = array('setup' => $setuptab);
}

ui_print_page_header ( __("Agent detail"), "images/bricks.png", false, "agent_status", false, $onheader);

// User is deleting agent
if (isset($result_delete)) {
	if ($result_delete)
		ui_print_success_message(__("Sucessfully deleted agent"));
	else
		ui_print_error_message(__("There was an error message deleting the agent"));
}	

echo '<form method="post" action="?sec=estado&sec2=operation/agentes/estado_agente&group_id=' . $group_id . '">';

echo '<table cellpadding="4" cellspacing="4" class="databox" width="98%">';
echo '<tr><td style="white-space:nowrap;">'.__('Group').': ';

$groups = users_get_groups ();
html_print_select_groups(false, "AR", true, 'group_id', $group_id, 'this.form.submit()', '', '', false, false, true, '', false, 'width:150px');

echo '</td><td style="white-space:nowrap;">';

echo ui_print_help_tip (__("Group recursion"), true);

html_print_checkbox ("recursion", 1, $recursion, false, false, 'this.form.submit()');

echo '</td><td style="white-space:nowrap;">';

echo __('Free text for search').' (*): ';

html_print_input_text ("search", $search, '', 8);

echo '</td><td style="white-space:nowrap;">';

$fields = array ();
$fields[0] = __('Normal'); 
$fields[1] = __('Warning');
$fields[2] = __('Critical');
$fields[3] = __('Unknown');
$fields[4] = __('Not normal'); 
$fields[5] = __('Not init');

echo '</td><td style="white-space:nowrap;">'.__('Agent status').': ';

html_print_select ($fields, "status", $status, 'this.form.submit()', __('All'), -1, false, false, true, '', false, 'width: 90px;');

echo '</td><td style="white-space:nowrap;">';

html_print_submit_button (__('Search'), "srcbutton", '', array ("class" => "sub search")); 

echo '</td><td style="width:5%;">&nbsp;</form></td>';

echo '<td>';
echo '<form method="post" action="index.php?sec=estado&sec2=godmode/agentes/configurar_agente">';
	html_print_input_hidden ('new_agent', 1);
	html_print_submit_button (__('Create agent'), 'crt', false, 'class="sub next"');
echo "</form>";
echo '</td>';

echo '</tr></table>';

if ($search != ""){
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

switch ($sortField) {
	case 'name':
		switch ($sort) {
			case 'up':
				$selectNameUp = $selected;
				$order = array('field' => 'nombre', 'order' => 'ASC');
				break;
			case 'down':
				$selectNameDown = $selected;
				$order = array('field' => 'nombre', 'order' => 'DESC');
				break;
		}
		break;
	case 'os':
		switch ($sort) {
			case 'up':
				$selectOsUp = $selected;
				$order = array('field' => 'id_os', 'order' => 'ASC');
				break;
			case 'down':
				$selectOsDown = $selected;
				$order = array('field' => 'id_os', 'order' => 'DESC');
				break;
		}
		break;
	case 'interval':
		switch ($sort) {
			case 'up':
				$selectIntervalUp = $selected;
				$order = array('field' => 'intervalo', 'order' => 'ASC');
				break;
			case 'down':
				$selectIntervalDown = $selected;
				$order = array('field' => 'intervalo', 'order' => 'DESC');
				break;
		}
		break;
	case 'group':
		switch ($sort) {
			case 'up':
				$selectGroupUp = $selected;
				$order = array('field' => 'id_grupo', 'order' => 'ASC');
				break;
			case 'down':
				$selectGroupDown = $selected;
				$order = array('field' => 'id_grupo', 'order' => 'DESC');
				break;
		}
		break;
	case 'last_contact':
		switch ($sort) {
			case 'up':
				$selectLastContactUp = $selected;
				$order = array('field' => 'ultimo_contacto', 'order' => 'ASC');
				break;
			case 'down':
				$selectLastContactDown = $selected;
				$order = array('field' => 'ultimo_contacto', 'order' => 'DESC');
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
		$order = array('field' => 'nombre', 'order' => 'ASC');
		break;
}

$search_sql = '';
if ($search != ""){
	$search_sql = " AND ( nombre COLLATE utf8_general_ci LIKE '%$search%' OR direccion LIKE '%$search%' OR comentarios LIKE '%$search%') ";
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

$total_agents = 0;
$agents = false;

$total_agents = agents_get_agents(array (
	'disabled' => 0,
	'id_grupo' => $groups,
	'search' => $search_sql,
	'status' => $status),
	array ('COUNT(*) as total'), 'AR', false);
$total_agents = isset ($total_agents[0]['total']) ? $total_agents[0]['total'] : 0;

$agents = agents_get_agents(array (
	'order' => 'nombre ASC',
	'id_grupo' => $groups,
	'disabled' => 0,
	'status' => $status,
	'search' => $search_sql,
	'offset' => (int) get_parameter ('offset'),
	'limit' => (int) $config['block_size']  ),

	array ('id_agente',
		'id_grupo',
		'id_os',
		'ultimo_contacto',
		'intervalo',
		'comentarios description'),
	'AR',
	$order);

if (empty ($agents)) {
	$agents = array ();
}

// Prepare pagination
ui_pagination ($total_agents, ui_get_url_refresh (array ('group_id' => $group_id, 'recursion' => $recursion, 'search' => $search, 'sort_field' => $sortField, 'sort' => $sort, 'status' => $status)));

// Show data.
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = "98%";
$table->class = "databox";

$table->head = array ();
$table->head[0] = __('Agent'). ' ' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=name&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=name&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';
$table->head[1] = __('Description');
$table->head[2] = __('OS'). ' ' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=os&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectOsUp, "alt" => "up"))  . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=os&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectOsDown, "alt" => "down")) . '</a>';
$table->head[3] = __('Interval'). ' ' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=interval&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectIntervalUp, "alt" => "up")) . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=interval&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectIntervalDown, "alt" => "down")) . '</a>';
$table->head[4] = __('Group'). ' ' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=group&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectGroupUp, "alt" => "up")) . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=group&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectGroupDown, "alt" => "down")) . '</a>';
$table->head[5] = __('Modules');
$table->head[6] = __('Status');
$table->head[7] = __('Alerts');
$table->head[8] = __('Last contact'). ' ' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=last_contact&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectLastContactUp, "alt" => "up")) . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=last_contact&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectLastContactDown, "alt" => "down")) . '</a>';

$table->align = array ();
		
//Only for AW flag
if (check_acl ($config["id_user"], $group_id, "AW")) {		
	$table->head[9] = __('R');
	$table->align[9] = "center";
	$table->head[10] = __('Delete');
	$table->align[10] = "center";
}

$table->align[2] = "center";
$table->align[3] = "center";
$table->align[4] = "center";
$table->align[5] = "center";
$table->align[6] = "center";
$table->align[7] = "center";
$table->align[8] = "right";

$table->style = array();
$table->style[0] = 'width: 15%';

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
		
	$agent_info["monitor_alertsfired"] = agents_get_alerts_fired ($agent["id_agente"]);
						
	$agent_info["monitor_critical"] = agents_monitor_critical ($agent["id_agente"]);
	$agent_info["monitor_warning"] = agents_monitor_warning ($agent["id_agente"]);
	$agent_info["monitor_unknown"] = agents_monitor_unknown ($agent["id_agente"]);
	$agent_info["monitor_normal"] = agents_monitor_ok ($agent["id_agente"]);
	
	$agent_info["alert_img"] = agents_tree_view_alert_img ($agent_info["monitor_alertsfired"]);
	
	$agent_info["status_img"] = agetns_tree_view_status_img ($agent_info["monitor_critical"],
															$agent_info["monitor_warning"],
															$agent_info["monitor_unknown"]);
															
	//Count all modules
	$agent_info["modules"] = $agent_info["monitor_critical"] + $agent_info["monitor_warning"] + $agent_info["monitor_unknown"] + $agent_info["monitor_normal"];
	
	$data = array ();
	
	$data[0] = '';		
	$data[0] .= '<span class="left">';
	$data[0] .= ui_print_agent_name($agent["id_agente"], true, 60, 'font-size:6.5pt !important;', true);
	$data[0] .= '</span>';
	$data[0] .= '<div class="left actions" style="visibility: hidden; clear: left">';
	$data[0] .= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent["id_agente"].'">'.__('View').'</a>';
	$data[0] .= ' | ';
	$data[0] .= '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent["id_agente"].'&tab=data">'.__('Data').'</a>';	
	if (check_acl ($config['id_user'], $agent["id_grupo"], "AW")) {
		$data[0] .= ' | ';		
		$data[0] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$agent["id_agente"].'">'.__('Edit').'</a>';
	}
	$data[0] .= '</div>';
				
	/*if (check_acl ($config['id_user'], $agent["id_grupo"], "AW")) {
		$data[0] .= '<a href="index.php?sec=estado&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$agent["id_agente"].'">';
		$data[0] .= html_print_image ("images/setup.png", true, array ("border" => 0, "width" => 16));
		$data[0] .= '</a>&nbsp;';
	}*/
	
	$data[1] = ui_print_truncate_text($agent["description"], 50, false, true, true, '[&hellip;]', 'font-size: 6.5pt');
	
	$data[2] = ui_print_os_icon ($agent["id_os"], false, true);

	//The interval we are thinking that it must be the agent interval in this
	//cell and it'snt the interval of modules.
//	if ($agent_info["interval"] > $agent["intervalo"]) {
//		$data[2] = '<span class="green">'.$agent_info["interval"].'</span>';
//	} else {
//		$data[2] = $agent["intervalo"];
//	}
	$data[3] = $agent["intervalo"];
	
	$data[4] = ui_print_group_icon ($agent["id_grupo"], true);
	
	$data[5] = '<b>';
	$data[5] .= $agent_info["modules"];

	if ($agent_info["monitor_alertsfired"] > 0)
		$data[5] .= ' : <span class="orange">'.$agent_info["monitor_alertsfired"].'</span>';
	if ($agent_info["monitor_critical"] > 0)
		$data[5] .= ' : <span class="red">'.$agent_info["monitor_critical"].'</span>';
	if ($agent_info["monitor_warning"] > 0)
		$data[5] .= ' : <span class="yellow">'.$agent_info["monitor_warning"].'</span>';
	if ($agent_info["monitor_unknown"] > 0)
		$data[5] .= ' : <span class="grey">'.$agent_info["monitor_unknown"].'</span>';
	if ($agent_info["monitor_normal"] > 0)
		$data[5] .= ' : <span class="green">'.$agent_info["monitor_normal"].'</span>';
	$data[5] .= '</b>';

	$data[6] = $agent_info["status_img"];
	
	$data[7] = $agent_info["alert_img"];


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
	
	//Only for AW flag
	if (check_acl ($config["id_user"], $group_id, "AW")) {
		// Has remote configuration ?
		$data[9]="";
		$agent_name = db_get_value("nombre", "tagente", "id_agente", $agent["id_agente"]);
		$agent_md5 = md5 ($agent_name, false);
		if (file_exists ($config["remote_config"]."/md5/".$agent_md5.".md5")) {
			$data[9] = "<a href='index.php?sec=estado&sec2=godmode/agentes/configurar_agente&tab=main&id_agente=".$agent["id_agente"]."&disk_conf=1'>".
			html_print_image("images/application_edit.png", true, array("align" => 'middle', "title" => __('Edit remote config')))."</a>";
		}
	
		$data[10] = 	"<a href='index.php?sec=estado&sec2=operation/agentes/estado_agente&
			borrar_agente=".$agent["id_agente"]."&group_id=$group_id&recursion=$recursion&search=$search&offset=$offset&sort_field=$sortField&sort=$sort'".
			' onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image('images/cross.png', true, array("border" => '0')) ."</a></td>";
	}

	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	html_print_table ($table);
	ui_pagination ($total_agents, ui_get_url_refresh (array ('group_id' => $group_id, 'search' => $search, 'sort_field' => $sortField, 'sort' => $sort, 'status' => $status)));
	unset ($table);
} else {
	echo '<div class="nf">'.__('There are no defined agents').'</div>';
}

/* Godmode controls SHOULD NOT BE HERE 

if (check_acl ($config['id_user'], 0, "LM") || check_acl ($config['id_user'], 0, "AW")
		|| check_acl ($config['id_user'], 0, "PM") || check_acl ($config['id_user'], 0, "DM")
		|| check_acl ($config['id_user'], 0, "UM")) {
	
	echo '<form method="post" action="index.php?sec=estado&amp;sec2=godmode/agentes/configurar_agente">';
		html_print_input_hidden ('new_agent', 1);
		html_print_submit_button (__('Create agent'), 'crt', false, 'class="sub next"');
	echo '</form>';
}
*/
?>

<script type="text/javascript">
$(document).ready (function () {
		
	$("table#table1 tr").hover (function () {
			$(".actions", this).css ("visibility", "");
		},
		function () {
			$(".actions", this).css ("visibility", "hidden");
	});		
		
	$("#group_id").click (
	function () {
		$(this).css ("width", "auto"); 
	});
			
	$("#group_id").blur (function () {
		$(this).css ("width", "180px"); 
	});
		
});
</script>
