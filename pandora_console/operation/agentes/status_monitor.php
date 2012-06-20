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

check_login();

if (! check_acl ($config['id_user'], 0, "AR") && ! check_acl ($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Agent Management");
	require ('general/noaccess.php');
	return;
}

require_once($config['homedir'] . '/include/functions_agents.php');
require_once($config['homedir'] . '/include/functions_modules.php');
require_once($config['homedir'] . '/include/functions_users.php');
$isFunctionPolicies = enterprise_include_once ('include/functions_policies.php');

$extra_sql = enterprise_hook('policies_get_agents_sql_condition');
if ($extra_sql === ENTERPRISE_NOT_HOOK) {
	$extra_sql = '';
}else if ($extra_sql != '') {
	$extra_sql .= ' OR ';
}

ui_print_page_header ("Monitor detail", "images/brick.png", false);


$ag_freestring = get_parameter ('ag_freestring');
$ag_modulename = (string) get_parameter ('ag_modulename');
$ag_group = (int) get_parameter ('ag_group', 0);
$offset = (int) get_parameter ('offset');
$status = (int) get_parameter ('status', 4);
$modulegroup = (int) get_parameter ('modulegroup', -1);
$sql_extra = '';
$refr = get_parameter('refr', 0);
// Sort functionality

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');

echo '<form method="post" action="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=' . $sortField . '&amp;sort=' . $sort .'">';

echo '<table cellspacing="4" cellpadding="4" width="98%" class="databox">';
echo '<tr><td valign="middle">'.__('Group').'</td>';
echo '<td valign="middle">';

html_print_select_groups(false, "AR", true, "ag_group", $ag_group, '',
	'', '0', false, false, false, 'w130', false, 'width:150px;');

echo "</td>";
echo "<td>".__('Monitor status')."</td><td>";

$fields = array ();
$fields[0] = __('Normal'); 
$fields[1] = __('Warning');
$fields[2] = __('Critical');
$fields[3] = __('Unknown');
$fields[4] = __('Not normal'); //default
$fields[5] = __('Not init');

html_print_select ($fields, "status", $status, '', __('All'), -1, false, false, true, '', false, 'width: 125px;');
echo '</td>';

echo '<td valign="middle">'.__('Module group').'</td>';
echo '<td valign="middle">';
$rows = db_get_all_rows_sql("SELECT * FROM tmodule_group ORDER BY name");
$rows = io_safe_output($rows);
$rows[0] = __('Not assigned');
html_print_select($rows, 'modulegroup', $modulegroup, '', __('All'), -1);

echo '</td></tr><tr><td valign="middle">'.__('Module name').'</td>';
echo '<td valign="middle">';

$user_groups = implode (",", array_keys (users_get_groups ()));

switch ($config["dbtype"]) {
	case "mysql":
	case "postgresql":
		$profiles = db_get_all_rows_sql('SELECT id_grupo
			FROM tusuario_perfil AS t1
				INNER JOIN tperfil AS t2 ON t1.id_perfil = t2.id_perfil
			WHERE t2.agent_view = 1 AND t1.id_usuario = \'' . $config['id_user'] .  '\';');
		if ($profiles === false)
			$profiles = array();
		
		$id_groups = array();
		$flag_all_group = false;
		foreach ($profiles as $profile) {
			if ($profile['id_grupo'] == 0) {
				$flag_all_group = true;
			}
			$id_groups[] = $profile['id_grupo'];
		}
		
		//The check of is_admin
		$flag_is_admin = (bool)db_get_value('is_admin', 'tusuario', 'id_user', $config['id_user']);
		
		$sql = ' SELECT distinct(nombre)
		FROM tagente_modulo
		WHERE nombre <> \'delete_pending\' AND id_agente IN
		(
			SELECT id_agente
			FROM tagente
			WHERE';
		
		$sql .= $extra_sql.'(';
		
		if ($flag_is_admin || $flag_all_group) {
			$sql .= ' 1 = 1 ';
		}
		else {
			if (empty($id_groups)) {
				$sql .= ' 1 = 0 ';
			}
			else {
				$sql .= ' id_grupo IN (' . implode(',', $id_groups) . ') ';
			}
		}
		
		$sql .= '))';
		break;
	case "oracle":
		$profiles = db_get_all_rows_sql('SELECT id_grupo
			FROM tusuario_perfil t1
				INNER JOIN tperfil t2 ON t1.id_perfil = t2.id_perfil
			WHERE t2.agent_view = 1 AND t1.id_usuario = \'' . $config['id_user'] .  '\';');
		if ($profiles === false)
			$profiles = array();
		
		$id_groups = array();
		$flag_all_group = false;
		foreach ($profiles as $profile) {
			if ($profile['id_grupo'] == 0) {
				$flag_all_group = true;
			}
			$id_groups[] = $profile['id_grupo'];
		}
		
		//The check of is_admin
		$flag_is_admin = (bool)db_get_value('is_admin', 'tusuario', 'id_user', $config['id_user']);
		
		$sql = ' SELECT distinct dbms_lob.substr(nombre,4000,1) as nombre
		FROM tagente_modulo
		WHERE dbms_lob.substr(nombre,4000,1) <> \'delete_pending\' AND id_agente IN
		(
			SELECT id_agente
			FROM tagente
			WHERE';
		
		$sql .= $extra_sql.'(';
		
		if ($flag_is_admin || $flag_all_group) {
			$sql .= ' 1 = 1 ';
		}
		else {
			if (empty($id_groups)) {
				$sql .= ' 1 = 0 ';
			}
			else {
				$sql .= ' id_grupo IN (' . implode(',', $id_groups) . ') ';
			}
		}
		
		$sql .= '))';
		break;
}

$modules = db_get_all_rows_sql($sql);

html_print_select (index_array ($modules, 'nombre', 'nombre'), "ag_modulename",
	$ag_modulename, '', __('All'), '', false, false, true, '', false, 'width: 150px;');

echo '</td><td valign="middle" align="right">'.__('Search').'</td>';

echo '<td valign="middle">';
html_print_input_text ("ag_freestring", $ag_freestring, '', 20,30, false);

echo '</td><td valign="middle">';
html_print_submit_button (__('Show'), "uptbutton", false, 'class="sub search"');

echo "</td><tr>";
echo "</table>";
echo "</form>";

// Sort functionality

$selected = 'border: 1px solid black;';
$selectAgentNameUp = '';
$selectAgentNameDown = '';
$selectTypeUp = '';
$selectTypeDown = '';
$selectModuleNameUp = '';
$selectModuleNameDown = '';
$selectIntervalUp = '';
$selectIntervalDown = '';
$selectStatusUp = '';
$selectStatusDown = '';
$selectDataUp = '';
$selectDataDown = '';
$selectTimestampUp = '';
$selectTimestampDown = '';
$order = null;

switch ($sortField) {
	case 'agent_name':
		switch ($sort) {
			case 'up':
				$selectAgentNameUp = $selected;
				$order = array('field' => 'tagente.nombre', 'order' => 'ASC');
				break;
			case 'down':
				$selectAgentNameDown = $selected;
				$order = array('field' => 'tagente.nombre', 'order' => 'DESC');
				break;
		}
		break;
	case 'type':
		switch ($sort) {
			case 'up':
				$selectTypeUp = $selected;
				$order = array('field' => 'tagente_modulo.id_tipo_modulo', 'order' => 'ASC');
				break;
			case 'down':
				$selectTypeDown = $selected;
				$order = array('field' => 'tagente_modulo.id_tipo_modulo', 'order' => 'DESC');
				break;
		}
		break;
	case 'module_name':
		switch ($sort) {
			case 'up':
				$selectModuleNameUp = $selected;
				$order = array('field' => 'tagente_modulo.nombre', 'order' => 'ASC');
				break;
			case 'down':
				$selectModuleNameDown = $selected;
				$order = array('field' => 'tagente_modulo.nombre', 'order' => 'DESC');
				break;
		}
		break;
	case 'interval':
		switch ($sort) {
			case 'up':
				$selectIntervalUp = $selected;
				$order = array('field' => 'tagente_modulo.module_interval', 'order' => 'ASC');
				break;
			case 'down':
				$selectIntervalDown = $selected;
				$order = array('field' => 'tagente_modulo.module_interval', 'order' => 'DESC');
				break;
		}
		break;
	case 'status':
		switch ($sort) {
			case 'up':
				$selectStatusUp = $selected;
				$order = array('field' => 'tagente_estado.estado', 'order' => 'ASC');
				break;
			case 'down':
				$selectStatusDown = $selected;
				$order = array('field' => 'tagente_estado.estado', 'order' => 'DESC');
				break;
		}
		break;
	case 'data':
		switch ($sort) {
			case 'up':
				$selectDataUp = $selected;
				$order = array('field' => 'tagente_estado.datos', 'order' => 'ASC');
				break;
			case 'down':
				$selectDataDown = $selected;
				$order = array('field' => 'tagente_estado.datos', 'order' => 'DESC');
				break;
		}
		break;
	case 'timestamp':
		switch ($sort) {
			case 'up':
				$selectTimestampUp = $selected;
				$order = array('field' => 'tagente_estado.utimestamp', 'order' => 'ASC');
				break;
			case 'down':
				$selectTimestampDown = $selected;
				$order = array('field' => 'tagente_estado.utimestamp', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectAgentNameUp = $selected;
		$selectAgentNameDown = '';
		$selectTypeUp = '';
		$selectTypeDown = '';
		$selectModuleNameUp = '';
		$selectModuleNameDown = '';
		$selectIntervalUp = '';
		$selectIntervalDown = '';
		$selectStatusUp = '';
		$selectStatusDown = '';
		$selectDataUp = '';
		$selectDataDown = '';
		$selectTimestampUp = '';
		$selectTimestampDown = '';
		$order = array('field' => 'tagente.nombre', 'order' => 'ASC');
		break;
}

// Begin Build SQL sentences
$sql = " FROM tagente, tagente_modulo, tagente_estado 
	WHERE $sql_extra (tagente.id_agente = tagente_modulo.id_agente 
	AND tagente_modulo.disabled = 0 
	AND tagente.disabled = 0 
	AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo";

// Agent group selector
if ($ag_group > 0 && check_acl ($config["id_user"], $ag_group, "AR")) {
	$sql .= sprintf (" AND tagente.id_grupo = %d", $ag_group);
}
elseif($user_groups != '') {
	// User has explicit permission on group 1 ?
	$sql .= " AND tagente.id_grupo IN (".$user_groups.")";
}

// Module group
if ($modulegroup > -1) {
	$sql .= sprintf (" AND tagente_modulo.id_module_group = '%d'", $modulegroup);
}

// Module name selector
if ($ag_modulename != "") {
	$sql .= sprintf (" AND tagente_modulo.nombre = '%s'", $ag_modulename);
}

// Freestring selector
if ($ag_freestring != "") {
	$sql .= sprintf (" AND (tagente.nombre LIKE '%%%s%%' OR tagente_modulo.nombre LIKE '%%%s%%' OR tagente_modulo.descripcion LIKE '%%%s%%')", $ag_freestring, $ag_freestring, $ag_freestring);
}

// Status selector
if ($status == 0) { //Normal
	$sql .= " AND tagente_estado.estado = 0 
	AND (utimestamp > 0 OR (tagente_modulo.id_tipo_modulo IN(21,22,23,100))) ";
}
elseif ($status == 2) { //Critical
	$sql .= " AND tagente_estado.estado = 1 AND utimestamp > 0";
}
elseif ($status == 1) { //Warning
	$sql .= " AND tagente_estado.estado = 2 AND utimestamp > 0";	
}
elseif ($status == 4) { //Not normal
	$sql .= " AND tagente_estado.estado <> 0";
} 
elseif ($status == 3) { //Unknown
	$sql .= " AND tagente_estado.estado = 3 AND tagente_estado.utimestamp <> 0";
}
elseif ($status == 5) { //Not init
	$sql .= " AND tagente_estado.utimestamp = 0 AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100)";	
}

// Build final SQL sentences
$count = db_get_sql ("SELECT COUNT(tagente_modulo.id_agente_modulo)". $sql . ")");
switch ($config["dbtype"]) {
	case "mysql":
		$sql = "SELECT tagente_modulo.id_agente_modulo,
			tagente.intervalo AS agent_interval,
			tagente.nombre AS agent_name, 
			tagente_modulo.nombre AS module_name,
			tagente_modulo.id_agente_modulo,
			tagente_modulo.history_data,
			tagente_modulo.flag AS flag,
			tagente.id_grupo AS id_group, 
			tagente.id_agente AS id_agent, 
			tagente_modulo.id_tipo_modulo AS module_type,
			tagente_modulo.module_interval, 
			tagente_estado.datos, 
			tagente_estado.estado,
			tagente_modulo.min_warning,
			tagente_modulo.max_warning,
			tagente_modulo.str_warning,
			tagente_modulo.unit,
			tagente_modulo.min_critical,
			tagente_modulo.max_critical,
			tagente_modulo.str_critical,
			tagente_modulo.extended_info,
			tagente_estado.utimestamp AS utimestamp".$sql.") ORDER BY " . $order['field'] . " " . $order['order'] 
			. " LIMIT ".$offset.",".$config["block_size"];
		break;
	case "postgresql":
		$sql = "SELECT tagente_modulo.id_agente_modulo,
			tagente.intervalo AS agent_interval,
			tagente.nombre AS agent_name, 
			tagente_modulo.nombre AS module_name,
			tagente_modulo.id_agente_modulo,
			tagente_modulo.history_data,
			tagente_modulo.flag AS flag,
			tagente.id_grupo AS id_group, 
			tagente.id_agente AS id_agent, 
			tagente_modulo.id_tipo_modulo AS module_type,
			tagente_modulo.module_interval, 
			tagente_estado.datos, 
			tagente_estado.estado,
			tagente_modulo.min_warning,
			tagente_modulo.max_warning,
			tagente_modulo.str_warning,
			tagente_modulo.min_critical,
			tagente_modulo.unit,
			tagente_modulo.max_critical,
			tagente_modulo.str_critical,
			tagente_modulo.extended_info,
			tagente_estado.utimestamp AS utimestamp".$sql.") LIMIT " . $config["block_size"] . " OFFSET " . $offset;
		break;
	case "oracle":
		$set = array();
		$set['limit'] = $config["block_size"];
		$set['offset'] = $offset;
		$sql = "SELECT tagente_modulo.id_agente_modulo,
			tagente.intervalo AS agent_interval,
			tagente.nombre AS agent_name, 
			tagente_modulo.nombre AS module_name,
			tagente_modulo.history_data,
			tagente_modulo.flag AS flag,
			tagente.id_grupo AS id_group, 
			tagente.id_agente AS id_agent, 
			tagente_modulo.id_tipo_modulo AS module_type,
			tagente_modulo.module_interval, 
			tagente_estado.datos, 
			tagente_estado.estado,
			tagente_modulo.min_warning,
			tagente_modulo.max_warning,
			tagente_modulo.str_warning,
			tagente_modulo.unit,
			tagente_modulo.min_critical,
			tagente_modulo.max_critical,
			tagente_modulo.str_critical,
			tagente_modulo.extended_info,
			tagente_estado.utimestamp AS utimestamp".$sql;
		$sql = oracle_recode_query ($sql, $set);
		break;
}
$result = db_get_all_rows_sql ($sql);

if ($count > $config["block_size"]) {
	ui_pagination ($count, false, $offset);
}

if ($result === false) {
	$result = array ();
}

if (($config['dbtype'] == 'oracle') && ($result !== false)) {
	for ($i=0; $i < count($result); $i++) {
		unset($result[$i]['rnum']);		
	}
}

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = "100%";
$table->class = "databox";

$table->head = array ();
$table->data = array ();
$table->size = array ();
$table->align = array ();

if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK)
	$table->head[0] = "<span title='" . __('Policy') . "'>" . __('P.') . "</span>";

$table->head[1] = __('Agent') . ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=agent_name&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectAgentNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=agent_name&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectAgentNameDown, "alt" => "down")) . '</a>';

$table->head[2] = __('Type'). ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=type&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectTypeUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=type&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectTypeDown, "alt" => "down")) . '</a>';

$table->align[2] = "left";

$table->head[3] = __('Module name') . ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=module_name&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectModuleNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=module_name&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectModuleNameDown, "alt" => "down")) . '</a>';


$table->head[4] = __('Interval') . ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=interval&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectIntervalUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=interval&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectIntervalDown, "alt" => "down")) . '</a>';

$table->align[4] = "center";

$table->head[5] = __('Status') . ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=status&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectStatusUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=status&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectStatusDown, "alt" => "down")) . '</a>';

$table->align[5] = "center";

$table->head[6] = __('Graph');
$table->align[6] = "center";

$table->head[7] = __('Warn');
$table->align[7] = "left";

$table->head[8] = __('Data') . ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=data&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectDataUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=data&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectDataDown, "alt" => "down")) . '</a>';

$table->align[8] = "left";

$table->head[9] = __('Timestamp') . ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=timestamp&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectTimestampUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=timestamp&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectTimestampDown, "alt" => "down")) . '</a>';

$table->align[9] = "right";

$rowPair = true;
$iterator = 0;
foreach ($result as $row) {
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;
	
	$data = array ();
	
	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$policyInfo = policies_info_module_policy($row['id_agente_modulo']);
		if ($policyInfo === false)
			$data[0] = '';
		else {
			$linked = policies_is_module_linked($row['id_agente_modulo']);
			
			$adopt = false;
			if (policies_is_module_adopt($row['id_agente_modulo'])) {
				$adopt = true;
			}
			
			if ($linked) {
				if ($adopt) {
					$img = 'images/policies_brick.png';
					$title = __('(Adopt) ') . $policyInfo['name_policy'];
				}
				else {
					$img = 'images/policies.png';
					$title = $policyInfo['name_policy'];
				}
			}
			else {
				if ($adopt) {
					$img = 'images/policies_not_brick.png';
					$title = __('(Unlinked) (Adopt) ') . $policyInfo['name_policy'];
				}
				else {
					$img = 'images/unlinkpolicy.png';
					$title = __('(Unlinked) ') . $policyInfo['name_policy'];
				}
			}
				
			$data[0] = '<a href="?sec=gpolicies&amp;sec2=enterprise/godmode/policies/policies&amp;id=' . $policyInfo['id_policy'] . '">' . 
				html_print_image($img,true, array('title' => $title)) .
				'</a>';
		}
	}
	
	$data[1] = '<strong><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$row["id_agent"].'">';
	$data[1] .= ui_print_truncate_text($row["agent_name"], 'agent_medium', false, true, false, '[&hellip;]', 'font-size:7.5pt;');
	$data[1] .= '</a></strong>';
	
	$data[2] = html_print_image("images/" . modules_show_icon_type ($row["module_type"]), true); 
	
	$data[3] = ui_print_truncate_text($row["module_name"], 'module_small', false, true, true);
	if ($row["extended_info"] != "") {
		$data[3] .= ui_print_help_tip ($row["extended_info"], true, '/images/comments.png');
	}
	
	$data[4] = ($row['module_interval'] == 0) ? $row['agent_interval'] : $row['module_interval'];
	
	if($row['utimestamp'] == 0 && (($row['module_type'] < 21 || $row['module_type'] > 23) && $row['module_type'] != 100)){
		$data[5] = ui_print_status_image(STATUS_MODULE_NO_DATA, __('NOT INIT'), true);
	}
	elseif ($row["estado"] == 0) {
		$data[5] = ui_print_status_image(STATUS_MODULE_OK, __('NORMAL').": ".$row["datos"], true);
	}
	elseif ($row["estado"] == 1) {
		$data[5] = ui_print_status_image(STATUS_MODULE_CRITICAL, __('CRITICAL').": ".$row["datos"], true);
	}
	elseif ($row["estado"] == 2) {
		$data[5] = ui_print_status_image(STATUS_MODULE_WARNING, __('WARNING').": ".$row["datos"], true);
	}
	else {
		$last_status =  modules_get_agentmodule_last_status($row['id_agente_modulo']);
		switch($last_status) {
			case 0:
				$data[5] = ui_print_status_image(STATUS_MODULE_UNKNOWN, __('UNKNOWN')." - ".__('Last status')." ".__('NORMAL').": ".$row["datos"], true);
				break;
			case 1:
				$data[5] = ui_print_status_image(STATUS_MODULE_UNKNOWN, __('UNKNOWN')." - ".__('Last status')." ".__('CRITICAL').": ".$row["datos"], true);
				break;
			case 2:
				$data[5] = ui_print_status_image(STATUS_MODULE_UNKNOWN, __('UNKNOWN')." - ".__('Last status')." ".__('WARNING').": ".$row["datos"], true);
				break;
		}
	}
	
	$data[6] = "";
	
	if ($row['history_data'] == 1) {
		
		$graph_type = return_graphtype ($row["module_type"]);
		
		$nombre_tipo_modulo = modules_get_moduletype_name ($row["module_type"]);
		$handle = "stat".$nombre_tipo_modulo."_".$row["id_agente_modulo"];
		$url = 'include/procesos.php?agente='.$row["id_agente_modulo"];
		$win_handle=dechex(crc32($row["id_agente_modulo"].$row["module_name"]));

		$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&period=86400&id=".$row["id_agente_modulo"]."&label=".base64_encode($row["module_name"])."&refresh=600','day_".$win_handle."')";

		$data[6] = '<a href="javascript:'.$link.'">' . html_print_image("images/chart_curve.png", true, array("border" => '0', "alt" => "")) .  '</a>';
		$data[6] .= "&nbsp;<a href='index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=".$row["id_agent"]."&amp;tab=data_view&period=86400&amp;id=".$row["id_agente_modulo"]."'>" . html_print_image('images/binary.png', true, array("style" => '0', "alt" => '')) . "</a>";
	}

	$data[7] = ui_print_module_warn_value($row['max_warning'], $row['min_warning'], $row['str_warning'], $row['max_critical'], $row['min_critical'], $row['str_critical']);

	if (is_numeric($row["datos"])){
		$salida = format_numeric($row["datos"]);

		// Show units ONLY in numeric data types
		if (isset($row["unit"])){
			$salida .= "&nbsp;" . '<i>'. io_safe_output($row["unit"]) . '</i>';
		}
	}
	else {
		$module_value = io_safe_output($row["datos"]);
		$sub_string = substr(io_safe_output($row["datos"]),0, 12);
		if ($module_value == $sub_string) {
			$salida = $module_value;
		}
		else {
			if (strlen($module_value) > 35)
				$mod_val = substr($module_value, 0, 35) . '...';
			else
				$mod_val = $module_value;
			
			$salida = html_print_input_hidden("value_replace_module_" . $row["id_agente_modulo"], $mod_val, true) 
			. "<span id='value_module_" . $row["id_agente_modulo"] . "'
				title='". $module_value ."' style='white-space: nowrap;'>" . 
				'<span id="value_module_text_' . $row["id_agente_modulo"] . '">' . $sub_string . '</span> ' .
				"<a href='javascript: toggle_full_value(" . $row["id_agente_modulo"] . ")'>" . html_print_image("images/rosette.png", true) . "" . "</span>";
		}			
	}

	$data[8] = $salida;
	
	if ($row["module_interval"] > 0)
		$interval = $row["module_interval"];
	else
		$interval = $row["agent_interval"];
	
	if ($row['estado'] == 3){
		$option = array ("html_attr" => 'class="redb"');
	} else {
		$option = array ();
	}
	$data[9] = ui_print_timestamp ($row["utimestamp"], true, $option);
	
	array_push ($table->data, $data);
}
if (!empty ($table->data)) {
	html_print_table ($table);
} else {
	echo '<div class="nf">'.__('This group doesn\'t have any monitor').'</div>';
}
?>
<script type="text/javascript">
function toggle_full_value(id) {
	value_title = $("#hidden-value_replace_module_" + id).val();
	
	$("#hidden-value_replace_module_" + id).val($("#value_module_text_" + id).html());

	$("#value_module_text_" + id).html(value_title);
}
</script>
