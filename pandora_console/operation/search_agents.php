<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

$searchAgents = check_acl($config['id_user'], 0, "AR");

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

$agents = false;
if ($searchAgents) {
	$sql = "
		FROM tagente AS t1
			INNER JOIN tgrupo
				ON tgrupo.id_grupo = t1.id_grupo
		WHERE (t1.id_grupo IN (
				SELECT id_grupo 
				FROM tusuario_perfil 
				WHERE id_usuario = '" . $config['id_user'] . "' 
					AND id_perfil IN (
						SELECT id_perfil 
						FROM tperfil WHERE agent_view = 1
					)
			)
			OR 0 IN (
				SELECT id_grupo
				FROM tusuario_perfil
				WHERE id_usuario = '" . $config['id_user'] . "'
				AND id_perfil IN (
					SELECT id_perfil
					FROM tperfil WHERE agent_view = 1
				) 
			)
		) AND
		t1.nombre COLLATE utf8_general_ci LIKE '%" . $stringSearchSQL . "%' OR
			tgrupo.nombre LIKE '%" . $stringSearchSQL . "%'";

	
	$select = "SELECT t1.id_agente, t1.ultimo_contacto, t1.nombre, t1.id_os, t1.intervalo, t1.id_grupo, t1.disabled";
	$limit = " ORDER BY " . $order['field'] . " " . $order['order'] . 
		" LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	
	$agents = process_sql($select . $sql . $limit);
	
	if($agents !== false) {
		$totalAgents = get_db_row_sql('SELECT COUNT(id_agente) AS agent_count ' . $sql);
		
		$totalAgents = $totalAgents['agent_count'];
	}
}


if (!$agents) {
	echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {		
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class = "databox";
	
	$table->head = array ();
	$table->head[0] = __('Agent') . ' ' .
		'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=name&sort=up"><img src="images/sort_up.png" style="' . $selectNameUp . '" /></a>' .
		'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=name&sort=down"><img src="images/sort_down.png" style="' . $selectNameDown . '" /></a>';
	$table->head[1] = __('OS'). ' ' .
		'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=os&sort=up"><img src="images/sort_up.png" style="' . $selectOsUp . '" /></a>' .
		'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=os&sort=down"><img src="images/sort_down.png" style="' . $selectOsDown . '" /></a>';
	$table->head[2] = __('Interval'). ' ' .
		'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=interval&sort=up"><img src="images/sort_up.png" style="' . $selectIntervalUp . '" /></a>' .
		'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=interval&sort=down"><img src="images/sort_down.png" style="' . $selectIntervalDown . '" /></a>';
	$table->head[3] = __('Group'). ' ' .
		'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=group&sort=up"><img src="images/sort_up.png" style="' . $selectGroupUp . '" /></a>' .
		'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=group&sort=down"><img src="images/sort_down.png" style="' . $selectGroupDown . '" /></a>';
	$table->head[4] = __('Modules');
	$table->head[5] = __('Status');
	$table->head[6] = __('Alerts');
	$table->head[7] = __('Last contact'). ' ' .
		'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=last_contact&sort=up"><img src="images/sort_up.png" style="' . $selectLastContactUp . '" /></a>' .
		'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=last_contact&sort=down"><img src="images/sort_down.png" style="' . $selectLastContactDown . '" /></a>';
	$table->head[8] = '';
	
	$table->align = array ();
	$table->align[0] = "left";
	$table->align[1] = "center";
	$table->align[2] = "center";
	$table->align[3] = "center";
	$table->align[4] = "center";
	$table->align[5] = "center";
	$table->align[6] = "center";
	$table->align[7] = "right";
	$table->align[8] = "center";
	
	$table->data = array ();
	
	foreach ($agents as $agent) {
		$agent_info = get_agent_module_info ($agent["id_agente"]);
		
		$modulesCell = '<b>'. $agent_info["modules"] . '</b>';
		if ($agent_info["monitor_alertsfired"] > 0)
			$modulesCell .= ' : <span class="orange">'.$agent_info["monitor_alertsfired"].'</span>';
		if ($agent_info["monitor_normal"] > 0)
			$modulesCell .= '</b> : <span class="green">'.$agent_info["monitor_normal"].'</span>';
		if ($agent_info["monitor_warning"] > 0)
			$modulesCell .= ' : <span class="yellow">'.$agent_info["monitor_warning"].'</span>';
		if ($agent_info["monitor_critical"] > 0)
			$modulesCell .= ' : <span class="red">'.$agent_info["monitor_critical"].'</span>';
		if ($agent_info["monitor_unknown"] > 0)
			$modulesCell .= ' : <span class="grey">'.$agent_info["monitor_unknown"].'</span>';
		
		if ($agent['disabled']) {
			$cellName = "<em>" . print_agent_name ($agent["id_agente"], true, "upper") .print_help_tip(__('Disabled'), true) . "</em>";
		}
		else {
			$cellName = print_agent_name ($agent["id_agente"], true, "upper");
		}
		
		$last_time = strtotime ($agent["ultimo_contacto"]);
		$now = time ();
		$diferencia = $now - $last_time;
		$time = print_timestamp ($last_time, true);
		$time_style = $time;
		if ($diferencia > ($agent["intervalo"] * 2))
			$time_style = '<b><span style="color: #ff0000">'.$time.'</span></b>';
		
		$manage_agent = '';
		if (check_acl ($config['id_user'], $agent['id_grupo'], "AW")) {
			$manage_agent = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='. $agent["id_agente"] . '">' .
				'<img title="' . __('Manage') . '" alt="' . __('Manage') . '" src="images/setup.png" /></a>';
		}
	
		array_push($table->data, array(
			$cellName,
			print_os_icon ($agent["id_os"], false, true),
			$agent['intervalo'],
			print_group_icon ($agent["id_grupo"], true),
			$modulesCell,
			$agent_info["status_img"],
			$agent_info["alert_img"],
			$time_style, $manage_agent));
	}
	
	echo "<br />";
	pagination ($totalAgents);
	print_table ($table);
	unset($table);
	pagination ($totalAgents);
}
?>
