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

require_once ("include/functions_reporting.php");
check_login ();

if (! give_acl ($config['id_user'], 0, "AR")) {
	pandora_audit("ACL Violation",
		"Trying to access agent main list view");
	require ("general/noaccess.php");
	return;
}

if (is_ajax ()) {
	$get_agent_module_last_value = (bool) get_parameter ('get_agent_module_last_value');
	$get_actions_alert_template = (bool) get_parameter("get_actions_alert_template");
	
	if ($get_actions_alert_template) {
		$id_template = get_parameter("id_template");
		$sql = sprintf ("SELECT t1.id, t1.name,
				(SELECT COUNT(t2.id) 
					FROM talert_templates AS t2 
					WHERE t2.id =  %d 
						AND t2.id_alert_action = t1.id) as 'sort_order'
			FROM talert_actions AS t1 
			ORDER BY sort_order DESC", $id_template);
			
		$rows = get_db_all_rows_sql($sql);
		
		
		if ($rows !== false)
			echo json_encode($rows);
		else
			echo "false";
		
		return;
	}
	
	if ($get_agent_module_last_value) {
		$id_module = (int) get_parameter ('id_agent_module');
		
		if (! give_acl ($config['id_user'], get_agentmodule_group ($id_module), "AR")) {
			pandora_audit("ACL Violation",
				"Trying to access agent main list view");
			echo json_encode (false);
			return;
		}
		echo json_encode (get_agent_module_last_value ($id_module));
		return;
	}
	
	return;
}

// Take some parameters (GET)
$group_id = (int) get_parameter ("group_id", 0);
$search = get_parameter ("search", "");
$offset = get_parameter('offset', 0);
$refr = get_parameter('refr', 0);

print_page_header ( __("Agent detail"), "images/bricks.png", false, "agent_status");

if ($group_id > 1) {
	echo '<form method="post" action="'.get_url_refresh (array ('group_id' => $group_id, 'offset' => 0)).'">';
} else {
	echo '<form method="post" action="'.get_url_refresh (array('offset' => 0)).'">';
}

echo '<table cellpadding="4" cellspacing="4" class="databox" width="95%">';
echo '<tr><td style="white-space:nowrap;">'.__('Group').': ';

$groups = get_user_groups ();
print_select_groups(false, "AR", true, 'group_id', $group_id, 'this.form.submit()', '', '');

echo '</td><td style="white-space:nowrap;">';

echo __('Free text for search').' (*): ';

print_input_text ("search", $search, '', 15);

echo '</td><td style="white-space:nowrap;">';

print_submit_button (__('Search'), "srcbutton", '', array ("class" => "sub search")); 

echo '</td><td style="width:40%;">&nbsp;</td></tr></table></form>';

if ($search != ""){
	$filter = array ("string" => '%'.$search.'%');
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

// Show only selected groups	
if ($group_id > 0) {
	$groups = $group_id;
	$agent_names = get_group_agents ($group_id, $filter, "upper");
// Not selected any specific group
} else {
	$user_group = get_user_groups ($config["id_user"], "AR");
	$groups = array_keys ($user_group);
	$agent_names = get_group_agents (array_keys ($user_group), $filter, "upper");
}

$total_agents = 0;
$agents = false;
if (! empty ($agent_names)) {
	$total_agents = get_agents (array (//'id_agente' => array_keys ($agent_names),
		'order' => 'nombre ASC',
		'disabled' => 0,
		'id_grupo' => $groups),
		array ('COUNT(*) as total'));
	$total_agents = isset ($total_agents[0]['total']) ? $total_agents[0]['total'] : 0;
	$agents = get_agents (array ('id_agente' => array_keys ($agent_names),
			'order' => 'nombre ASC',
			'id_grupo' => $groups,
			'offset' => (int) get_parameter ('offset'),
			'limit' => (int) $config['block_size']),
		array ('id_agente',
			'id_grupo',
			'id_os',
			'ultimo_contacto',
			'intervalo'),
		'AR',
		$order);
}

if (empty ($agents)) {
	$agents = array ();
}

// Prepare pagination
pagination ($total_agents, get_url_refresh (array ('group_id' => $group_id, 'search' => $search, 'sort_field' => $sortField, 'sort' => $sort)));

// Show data.
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = "98%";
$table->class = "databox";

$table->head = array ();
$table->head[0] = __('Agent'). ' ' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;search=' . $search . '&amp;sort_field=name&amp;sort=up"><img src="images/sort_up.png" style="' . $selectNameUp . '" alt="up" /></a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;search=' . $search . '&amp;sort_field=name&amp;sort=down"><img src="images/sort_down.png" style="' . $selectNameDown . '" alt="down" /></a>';
$table->head[1] = __('OS'). ' ' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;search=' . $search . '&amp;sort_field=os&amp;sort=up"><img src="images/sort_up.png" style="' . $selectOsUp . '" alt="up" /></a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;search=' . $search . '&amp;sort_field=os&amp;sort=down"><img src="images/sort_down.png" style="' . $selectOsDown . '" alt="down" /></a>';
$table->head[2] = __('Interval'). ' ' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;search=' . $search . '&amp;sort_field=interval&amp;sort=up"><img src="images/sort_up.png" style="' . $selectIntervalUp . '" alt="up" /></a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;search=' . $search . '&amp;sort_field=interval&amp;sort=down"><img src="images/sort_down.png" style="' . $selectIntervalDown . '" alt="down" /></a>';
$table->head[3] = __('Group'). ' ' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;search=' . $search . '&amp;sort_field=group&amp;sort=up"><img src="images/sort_up.png" style="' . $selectGroupUp . '" alt="up" /></a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;search=' . $search . '&amp;sort_field=group&amp;sort=down"><img src="images/sort_down.png" style="' . $selectGroupDown . '" alt="down" /></a>';
$table->head[4] = __('Modules');
$table->head[5] = __('Status');
$table->head[6] = __('Alerts');
$table->head[7] = __('Last contact'). ' ' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;search=' . $search . '&amp;sort_field=last_contact&amp;sort=up"><img src="images/sort_up.png" style="' . $selectLastContactUp . '" alt="up" /></a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;search=' . $search . '&amp;sort_field=last_contact&amp;sort=down"><img src="images/sort_down.png" style="' . $selectLastContactDown . '" alt="down" /></a>';

$table->align = array ();
$table->align[1] = "center";
$table->align[2] = "center";
$table->align[3] = "center";
$table->align[4] = "center";
$table->align[5] = "center";
$table->align[6] = "center";
$table->align[7] = "right";

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
		
	$agent_info = get_agent_module_info ($agent["id_agente"]);
	
	$data = array ();
	
	$data[0] = '';
	if (give_acl ($config['id_user'], $agent["id_grupo"], "AW")) {
		$data[0] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$agent["id_agente"].'">';
		$data[0] .= print_image ("images/setup.png", true, array ("border" => 0, "width" => 16));
		$data[0] .= '</a>&nbsp;';
	}
		
	$data[0] .= print_agent_name($agent["id_agente"], true, 0, 'none', true);
	
	$data[1] = print_os_icon ($agent["id_os"], false, true);

	//The interval we are thinking that it must be the agent interval in this
	//cell and it'snt the interval of modules.
//	if ($agent_info["interval"] > $agent["intervalo"]) {
//		$data[2] = '<span class="green">'.$agent_info["interval"].'</span>';
//	} else {
//		$data[2] = $agent["intervalo"];
//	}
	$data[2] = $agent["intervalo"];
	
	$data[3] = print_group_icon ($agent["id_grupo"], true);
	
	$data[4] = '<b>';
	$data[4] .= $agent_info["modules"];

	if ($agent_info["monitor_alertsfired"] > 0)
		$data[4] .= ' : <span class="orange">'.$agent_info["monitor_alertsfired"].'</span>';
	if ($agent_info["monitor_critical"] > 0)
		$data[4] .= ' : <span class="red">'.$agent_info["monitor_critical"].'</span>';
	if ($agent_info["monitor_warning"] > 0)
		$data[4] .= ' : <span class="yellow">'.$agent_info["monitor_warning"].'</span>';
	if ($agent_info["monitor_unknown"] > 0)
		$data[4] .= ' : <span class="grey">'.$agent_info["monitor_unknown"].'</span>';
	if ($agent_info["monitor_normal"] > 0)
		$data[4] .= ' : <span class="green">'.$agent_info["monitor_normal"].'</span>';
	$data[4] .= '</b>';

	$data[5] = $agent_info["status_img"];
	
	$data[6] = $agent_info["alert_img"];


	$last_time = strtotime ($agent["ultimo_contacto"]);
	$now = time ();
	$diferencia = $now - $last_time;
	$time = print_timestamp ($last_time, true);
	$style = '';
	if ($diferencia > ($agent["intervalo"] * 2))
		$data[7] = '<b><span style="color: #ff0000">'.$time.'</span></b>';
	else
		$data[7] = $time;

	// This old code was returning "never" on agents without modules, BAD !!
	// And does not print outdated agents in red. WRONG !!!!
	// $data[7] = print_timestamp ($agent_info["last_contact"], true);

	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	print_table ($table);
	pagination ($total_agents, get_url_refresh (array ('group_id' => $group_id, 'search' => $search, 'sort_field' => $sortField, 'sort' => $sort)));
	unset ($table);
} else {
	echo '<div class="nf">'.__('There are no agents included in this group').'</div>';
}

/* Godmode controls SHOULD NOT BE HERE 

if (give_acl ($config['id_user'], 0, "LM") || give_acl ($config['id_user'], 0, "AW")
		|| give_acl ($config['id_user'], 0, "PM") || give_acl ($config['id_user'], 0, "DM")
		|| give_acl ($config['id_user'], 0, "UM")) {
	
	echo '<form method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente">';
		print_input_hidden ('new_agent', 1);
		print_submit_button (__('Create agent'), 'crt', false, 'class="sub next"');
	echo '</form>';
}
*/
?>
