<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

enterprise_include_once('include/functions_policies.php');
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . '/include/functions_users.php');

// TODO: CLEAN extra_sql
$extra_sql = '';

$searchModules = check_acl($config['id_user'], 0, "AR");

$selectModuleNameUp = '';
$selectModuleNameDown = '';
$selectAgentNameUp = '';
$selectAgentNameDown = '';

switch ($sortField) {
	case 'module_name':
		switch ($sort) {
			case 'up':
				$selectModuleNameUp = $selected;
				$order = array('field' => 'module_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectModuleNameDown = $selected;
				$order = array('field' => 'module_name', 'order' => 'DESC');
				break;
		}
		break;
	case 'agent_name':
		switch ($sort) {
			case 'up':
				$selectAgentNameUp = $selected;
				$order = array('field' => 'agent_name', 'order' => 'ASC');
				break;
			case 'down':
				$selectAgentNameDown = $selected;
				$order = array('field' => 'agent_name', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectModuleNameUp = $selected;
		$order = array('field' => 'module_name', 'order' => 'ASC');
		break;
}


$modules = false;
if ($searchModules) {
	$userGroups = users_get_groups($config['id_user'], 'AR', false);
	$id_userGroups = array_keys($userGroups);
	
	switch ($config["dbtype"]) {
		case "mysql":
			$chunk_sql = '
				FROM tagente_modulo AS t1
					INNER JOIN tagente AS t2
						ON t2.id_agente = t1.id_agente
					INNER JOIN tgrupo AS t3
						ON t3.id_grupo = t2.id_grupo
					INNER JOIN tagente_estado AS t4
						ON t4.id_agente_modulo = t1.id_agente_modulo
				WHERE ('.$extra_sql.'t2.id_grupo IN (' . implode(',', $id_userGroups) . ')
						OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario = "' . $config['id_user'] . '"
							AND id_perfil IN (
								SELECT id_perfil
								FROM tperfil WHERE agent_view = 1
							) 
						)
					) AND
					t1.nombre COLLATE utf8_general_ci LIKE "%' . $stringSearchSQL . '%" OR
					t3.nombre LIKE "%' . $stringSearchSQL . '%"';
			break;
		case "postgresql":
			$chunk_sql = '
				FROM tagente_modulo AS t1
					INNER JOIN tagente AS t2
						ON t2.id_agente = t1.id_agente
					INNER JOIN tgrupo AS t3
						ON t3.id_grupo = t2.id_grupo
					INNER JOIN tagente_estado AS t4
						ON t4.id_agente_modulo = t1.id_agente_modulo
				WHERE ('.$extra_sql.'t2.id_grupo IN (' . implode(',', $id_userGroups) . ')
						OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario = \'' . $config['id_user'] . '\'
							AND id_perfil IN (
								SELECT id_perfil
								FROM tperfil WHERE agent_view = 1
							) 
						)
					) AND
					t1.nombre COLLATE utf8_general_ci LIKE \'%' . $stringSearchSQL . '%\' OR
					t3.nombre LIKE \'%' . $stringSearchSQL . '%\'';
			break;
		case "oracle":
			$chunk_sql = '
				FROM tagente_modulo AS t1
					INNER JOIN tagente AS t2
						ON t2.id_agente = t1.id_agente
					INNER JOIN tgrupo AS t3
						ON t3.id_grupo = t2.id_grupo
					INNER JOIN tagente_estado AS t4
						ON t4.id_agente_modulo = t1.id_agente_modulo
				WHERE ' . $subquery_enterprise . ' (t2.id_grupo IN (' . implode(',', $id_userGroups) . ')
						OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario = \'' . $config['id_user'] . '\'
							AND id_perfil IN (
								SELECT id_perfil
								FROM tperfil WHERE agent_view = 1
							) 
						)
					) AND
					UPPER(t1.nombre) LIKE UPPER(\'%' . $stringSearchSQL . '%\') OR
					t3.nombre LIKE \'%' . $stringSearchSQL . '%\'';
			break;
	}
	
	$select = "SELECT *, t1.nombre AS module_name, t2.nombre AS agent_name ";
	$limit = " ORDER BY " . $order['field'] . " " . $order['order'] . 
		" LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
	
	$modules = db_get_all_rows_sql($select . $chunk_sql . $limit);
}

if (!$modules) {
		echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {
	$totalModules = db_get_all_rows_sql("SELECT COUNT(t1.id_agente_modulo) AS count_modules " . $chunk_sql);
	$totalModules = $totalModules[0]['count_modules'];
	
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class = "databox";
	
	$table->head = array ();
	$table->head[0] = __('Module') . ' ' .
		'<a href="index.php?search_category=modules&keywords=' . 
			$config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . 
			'&sort_field=module_name&sort=up">'. html_print_image("images/sort_up.png", true, array("style" => $selectModuleNameUp)) . '</a>' .
		'<a href="index.php?search_category=modules&keywords=' .
			$config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . 
			'&sort_field=module_name&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectModuleNameDown)) . '</a>';	
	$table->head[1] = __('Agent') . ' ' .
		'<a href="index.php?search_category=modules&keywords=' . 
			$config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . 
			'&sort_field=agent_name&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectAgentNameUp)) . '</a>' .
		'<a href="index.php?search_category=modules&keywords=' .
			$config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . 
			'&sort_field=agent_name&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectAgentNameDown)) .'</a>';
	$table->head[2] = __('Type');
	$table->head[3] = __('Interval');
	$table->head[4] = __('Status');
	$table->head[5] = __('Graph');
	$table->head[6] = __('Data');
	$table->head[7] = __('Timestamp');
	
	
	
	$table->align = array ();
	$table->align[0] = "left";
	$table->align[1] = "left";
	$table->align[2] = "center";
	$table->align[3] = "center";
	$table->align[4] = "center";
	$table->align[5] = "center";
	$table->align[6] = "right";
	$table->align[7] = "right";
	
	$table->data = array ();
	
	foreach ($modules as $module) {
		$agentCell = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $module['id_agente'] . '">' .
			$module['agent_name'] . '</a>';
		
		$typeCell = ui_print_moduletype_icon($module["id_tipo_modulo"], true);
		
		$intervalCell = ($module['module_interval'] == 0) ? $module['agent_interval'] : $module['intervalo'];
		
		if($module['utimestamp'] == 0 && (($module['module_type'] < 21 || $module['module_type'] > 23) && $module['module_type'] != 100)){
			$statusCell = ui_print_status_image(STATUS_MODULE_NO_DATA, __('NOT INIT'), true);
		}
		elseif ($module["estado"] == 0) {
			$statusCell = ui_print_status_image(STATUS_MODULE_OK, __('NORMAL').": ".$module["datos"], true);
		}
		elseif ($module["estado"] == 1) {
			$statusCell = ui_print_status_image(STATUS_MODULE_CRITICAL, __('CRITICAL').": ".$module["datos"], true);
		}
		elseif ($module["estado"] == 2) {
			$statusCell = ui_print_status_image(STATUS_MODULE_WARNING, __('WARNING').": ".$module["datos"], true);
		}
		else {
			$last_status = modules_get_agentmodule_last_status($module['id_agente_modulo']);
			switch($last_status) {
				case 0:
					$statusCell = ui_print_status_image(STATUS_MODULE_OK, __('UNKNOWN')." - ".__('Last status')." ".__('NORMAL').": ".$module["datos"], true);
					break;
				case 1:
					$statusCell = ui_print_status_image(STATUS_MODULE_CRITICAL, __('UNKNOWN')." - ".__('Last status')." ".__('CRITICAL').": ".$module["datos"], true);
					break;
				case 2:
					$statusCell = ui_print_status_image(STATUS_MODULE_WARNING, __('UNKNOWN')." - ".__('Last status')." ".__('WARNING').": ".$module["datos"], true);
					break;
			}
		}
		
		$graphCell = "";
		if ($module['history_data'] == 1){
	
			$graph_type = return_graphtype ($module["id_tipo_modulo"]);
	
			$name_module_type = modules_get_moduletype_name ($module["id_tipo_modulo"]);
			$handle = "stat" . $name_module_type . "_" . $module["id_agente_modulo"];
			$url = 'include/procesos.php?agente=' . $module["id_agente_modulo"];
			$win_handle = dechex(crc32($module["id_agente_modulo"] . $module["module_name"]));
	
			$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&period=86400&id=".$module["id_agente_modulo"]."&label=".base64_encode($module["module_name"])."&refresh=600','day_".$win_handle."')";
	
			$graphCell = '<a href="javascript:'.$link.'">' . html_print_image("images/chart_curve.png", true, array("border" => 0, "alt" => "")) . '</a>';
			$graphCell .= "&nbsp;<a href='index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=".$module["id_agente"]."&amp;tab=data_view&period=86400&amp;id=".$module["id_agente_modulo"]."'>" . html_print_image('images/binary.png', true, array("border" => "0", "alt" => "")) . "</a>";
		}
		
		if (is_numeric($module["datos"]))
			$dataCell = format_numeric($module["datos"]);
		else
			$dataCell = "<span title='".$module['datos']."' style='white-space: nowrap;'>".substr(io_safe_output($module["datos"]),0,12)."</span>";
		
		if ($module['estado'] == 3){
			$option = array ("html_attr" => 'class="redb"');
		} else {
			$option = array ();
		}
		$timestampCell = ui_print_timestamp ($module["utimestamp"], true, $option);
		
		
		array_push($table->data, array(
			$module['module_name'],
			$agentCell,
			$typeCell,
			$intervalCell,
			$statusCell,
			$graphCell,
			$dataCell,
			$timestampCell));
	}
	
	echo "<br />";
	ui_pagination ($totalModules);
	html_print_table ($table);
	unset($table);
	ui_pagination ($totalModules);
}	
?>
