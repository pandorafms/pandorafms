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

if (! check_acl ($config['id_user'], 0, "AR")
	&& ! check_acl ($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Agent Management");
	require ('general/noaccess.php');
	return;
}

require_once($config['homedir'] . '/include/functions_agents.php');
require_once($config['homedir'] . '/include/functions_modules.php');
require_once($config['homedir'] . '/include/functions_users.php');
enterprise_include_once ('include/functions_metaconsole.php');

$isFunctionPolicies = enterprise_include_once ('include/functions_policies.php');

if (! defined ('METACONSOLE')) {
	//Header
	ui_print_page_header ("Monitor detail", "images/brick.png", false);
}
else {
	
	ui_meta_print_header(__("Monitor view"));
}

$ag_freestring = get_parameter ('ag_freestring');
$ag_modulename = (string) get_parameter ('ag_modulename');
$ag_group = get_parameter ('ag_group', 0);
$offset = (int) get_parameter ('offset', 0);
$status = (int) get_parameter ('status', 4);
$modulegroup = get_parameter ('modulegroup', -1);
$tag_filter = get_parameter('tag_filter', 0);
$refr = get_parameter('refr', 0);
// Sort functionality

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');

echo '<form method="post" action="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=' . $sortField . '&amp;sort=' . $sort .'">';

echo '<table cellspacing="4" cellpadding="4" width="98%" class="databox">
	<tr>';

// Get Groups and profiles from user
$user_groups = implode (",", array_keys (users_get_groups ()));

////////////////////////////////////
// Begin Build SQL sentences
$sql_from = " FROM tagente, tagente_modulo, tagente_estado ";

$sql_conditions_base = " WHERE tagente.id_agente = tagente_modulo.id_agente 
		AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo";

$sql_conditions = " AND tagente_modulo.disabled = 0 AND tagente.disabled = 0";

// Agent group selector
if (!defined('METACONSOLE')) {
	if ($ag_group > 0 && check_acl ($config["id_user"], $ag_group, "AR")) {
		$sql_conditions_group = sprintf (" AND tagente.id_grupo = %d", $ag_group);
	}
	elseif ($user_groups != '') {
		// User has explicit permission on group 1 ?
		$sql_conditions_group = " AND tagente.id_grupo IN (".$user_groups.")";
	}
}
else {
	if ($ag_group != "0" && check_acl ($config["id_user"], $ag_group, "AR")) {
		$sql_conditions_group = sprintf (" AND tagente.id_grupo IN ( SELECT id_grupo FROM tgrupo where nombre = '%s') ", $ag_group);
	}
	elseif ($user_groups != '') {
		// User has explicit permission on group 1 ?
		$sql_conditions_group = " AND tagente.id_grupo IN (".$user_groups.")";
	}
}

// Module group
if (defined('METACONSOLE')) {
	if ($modulegroup != '-1')
		$sql_conditions .= sprintf (" AND tagente_modulo.id_module_group IN (SELECT id_mg 
			FROM tmodule_group WHERE name = '%s')", $modulegroup);	
}
else if ($modulegroup > -1) {
	$sql_conditions .= sprintf (" AND tagente_modulo.id_module_group = '%d'", $modulegroup);

}

// Module name selector
if ($ag_modulename != "") {
	$sql_conditions .= sprintf (" AND tagente_modulo.nombre = '%s'",
		$ag_modulename);
}

// Freestring selector
if ($ag_freestring != "") {
	$sql_conditions .= sprintf (" AND (tagente.nombre LIKE '%%%s%%'
		OR tagente_modulo.nombre LIKE '%%%s%%'
		OR tagente_modulo.descripcion LIKE '%%%s%%')",
		$ag_freestring, $ag_freestring, $ag_freestring);
}

// Status selector
if ($status == AGENT_MODULE_STATUS_NORMAL) { //Normal
	$sql_conditions .= " AND tagente_estado.estado = 0 
	AND (utimestamp > 0 OR (tagente_modulo.id_tipo_modulo IN(21,22,23,100))) ";
}
elseif ($status == AGENT_MODULE_STATUS_CRITICAL_BAD) { //Critical
	$sql_conditions .= " AND tagente_estado.estado = 1 AND utimestamp > 0";
}
elseif ($status == AGENT_MODULE_STATUS_WARNING) { //Warning
	$sql_conditions .= " AND tagente_estado.estado = 2 AND utimestamp > 0";	
}
elseif ($status == AGENT_MODULE_STATUS_NOT_NORMAL) { //Not normal
	$sql_conditions .= " AND tagente_estado.estado <> 0";
} 
elseif ($status == AGENT_MODULE_STATUS_UNKNOW) { //Unknown
	$sql_conditions .= " AND tagente_estado.estado = 3 AND tagente_estado.utimestamp <> 0";
}
elseif ($status == AGENT_MODULE_STATUS_NOT_INIT) { //Not init
	$sql_conditions .= " AND tagente_estado.utimestamp = 0
		AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100)";
}

//Filter by tag
if ($tag_filter !== 0) {
	if (defined('METACONSOLE')) {
		$sql_conditions .= " AND tagente_modulo.id_agente_modulo IN (
				SELECT ttag_module.id_agente_modulo
				FROM ttag_module
				WHERE ttag_module.id_tag IN (SELECT id_tag FROM ttag where name LIKE '%" . $tag_filter . "%')
			)";
	}
	else{
		$sql_conditions .= " AND tagente_modulo.id_agente_modulo IN (
				SELECT ttag_module.id_agente_modulo
				FROM ttag_module
				WHERE ttag_module.id_tag = " . $tag_filter . "
			)";
	
	}
}

$sql_conditions_tags = tags_get_acl_tags($config['id_user'], $ag_group, 'AR', 'module_condition', 'AND', 'tagente_modulo'); 

// Two modes of filter. All the filters and only ACLs filter
$sql_conditions_all = $sql_conditions_base . $sql_conditions . $sql_conditions_group . $sql_conditions_tags;
$sql_conditions_acl = $sql_conditions_base . $sql_conditions_group . $sql_conditions_tags;

// Get count to paginate
if (!defined('METACONSOLE')) 
	$count = db_get_sql ("SELECT COUNT(tagente_modulo.id_agente_modulo) " . $sql_from . $sql_conditions_all);

// Get limit_sql depend of the metaconsole or standard mode
if (defined('METACONSOLE')) {
	// Offset will be used to get the subset of modules
	$inferior_limit = $offset;
	$superior_limit = $config["block_size"] + $offset;
	// Offset reset to get all elements
	$offset = 0;
	if (!isset($config["meta_num_elements"]))
		$config["meta_num_elements"] = 100;
	
	$limit_sql = $config["meta_num_elements"];
}
else
	$limit_sql = $config["block_size"];

// End Build SQL sentences
/////////////////////////////////////

// Query to get name of the modules to module name filter combo
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
		
		$sql = ' SELECT distinct(tagente_modulo.nombre)
			'. $sql_from . $sql_conditions_acl;
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
		$flag_is_admin = (bool)db_get_value('is_admin', 'tusuario',
			'id_user', $config['id_user']);
		
		$sql = ' SELECT DISTINCT dbms_lob.substr(nombre,4000,1) AS nombre' .
			$sql_from . $sql_conditions_acl;
		break;
}

$modules = array();
$tags = array();
$rows_select = array();
$rows_temp_processed = array();
$groups_select = array();
if ($flag_is_admin)
	$groups_select[0] = __('All');

if (defined('METACONSOLE')) {
	
	// For each server defined and not disabled:
	$servers = db_get_all_rows_sql ("SELECT * FROM tmetaconsole_setup WHERE disabled = 0");
	
	if ($servers === false)
		$servers = array();
	
	$result = array();
	foreach($servers as $server) {
		// If connection was good then retrieve all data server
		if (metaconsole_connect($server) == NOERR) {
			$connection = true;
		}
		else {
			$connection = false;
		}
		
		// Get all info for filters of all nodes
		$modules_temp = db_get_all_rows_sql($sql);
		
		$tags_temp = db_get_all_rows_sql('
			SELECT name, name
			FROM ttag
				WHERE id_tag IN (SELECT ttag_module.id_tag
					FROM ttag_module)');
		
		$rows_temp = db_get_all_rows_sql("SELECT distinct name
			FROM tmodule_group
			ORDER BY name");
		$rows_temp = io_safe_output($rows_temp);
		
		if (!empty($rows_temp)) {
			foreach ($rows_temp as $module_group_key => $modules_group_val)
				$rows_temp_processed[$modules_group_val['name']] = $modules_group_val['name'];
			
			$rows_select = array_unique(array_merge($rows_select, $rows_temp_processed));
		}
		
		$groups_temp = users_get_groups_for_select(false, "AR", true, true, false);									
		
		$groups_temp_processed = array();
		
		foreach ($groups_temp as $group_temp_key => $group_temp_val) {
			$new_key = str_replace('&nbsp;','',$group_temp_val);
			$groups_temp_processed[$new_key] = $group_temp_val;
		}
		
		if (!empty($groups_temp_processed)) {
			$groups_select = array_unique(array_merge($groups_select, $groups_temp_processed));
		}
		
		if (!empty($modules_temp))
			$modules = array_merge($modules, $modules_temp);
		if (!empty($tags_temp))
			$tags = array_merge($tags, $tags_temp);
		
		metaconsole_restore_db();
	}
	unset($groups_select[__('All')]);
}

if (!defined('METACONSOLE')) {
	echo '
		<td valign="middle">' . __('Group') . '</td>
		<td valign="middle">' . 
			html_print_select_groups(false, "AR", true, "ag_group",
				$ag_group, '', '', '0', true, false, false, 'w130',
				false, 'width:150px;') . '
		</td>';
}
else { 
	echo '
		<td valign="middle">' . __('Group') . '</td>
		<td valign="middle">' .
			html_print_select($groups_select, "ag_group",
				io_safe_output($ag_group), '', '', '0', true, false, false, 'w130',
				false, 'width:150px;') . '
		</td>';
}
echo '<td>' . __('Monitor status') . "</td>";



echo "<td>";

$fields = array ();
$fields[AGENT_MODULE_STATUS_NORMAL] = __('Normal'); 
$fields[AGENT_MODULE_STATUS_WARNING] = __('Warning');
$fields[AGENT_MODULE_STATUS_CRITICAL_BAD] = __('Critical');
$fields[AGENT_MODULE_STATUS_UNKNOW] = __('Unknown');
$fields[AGENT_MODULE_STATUS_NOT_NORMAL] = __('Not normal'); //default
$fields[AGENT_MODULE_STATUS_NOT_INIT] = __('Not init');

html_print_select ($fields, "status", $status, '', __('All'), -1,
	false, false, true, '', false, 'width: 125px;');
echo '</td>';



echo '<td valign="middle">' . __('Module group') . '</td>';
echo '<td valign="middle">';
if (!defined('METACONSOLE')) {
	$rows = db_get_all_rows_sql("SELECT *
		FROM tmodule_group ORDER BY name");
	$rows = io_safe_output($rows);
	$rows_select = array();
	if (!empty($rows))
		foreach ($rows as $module_group)
			$rows_select[$module_group['id_mg']] = $module_group['name'];
}

$rows_select[0] = __('Not assigned');

html_print_select($rows_select, 'modulegroup', $modulegroup, '', __('All'), -1);
echo '</td>';



echo '</tr>';

echo '<tr>';



echo '<td valign="middle">' . __('Module name') . '</td>';
echo '<td valign="middle">';

if (!defined('METACONSOLE'))
	$modules = db_get_all_rows_sql($sql);

html_print_select (index_array ($modules, 'nombre', 'nombre'), "ag_modulename",
	$ag_modulename, '', __('All'), '', false, false, true, '', false, 'width: 150px;');

echo '</td>';



echo '<td valign="middle" align="right">' .
	__('Tags') .
	ui_print_help_tip(__('Only it is show tags in use.'), true) .
	'</td>';
echo '<td>';

if (!defined('METACONSOLE')) {
	$tags = tags_get_user_tags();
}

if (empty($tags)) {
	echo __('No tags');
}
else {
	if (!defined('METACONSOLE'))
		html_print_select ($tags, "tag_filter",
			$tag_filter, '', __('All'), '', false, false, true, '', false, 'width: 150px;');
	else
		html_print_select (index_array($tags, 'name', 'name'), "tag_filter",
			$tag_filter, '', __('All'), '', false, false, true, '', false, 'width: 150px;');
}
echo '</td>';



echo '<td valign="middle" align="right">' .
	__('Search') .
	'</td>';
echo '<td valign="middle">';
html_print_input_text ("ag_freestring", $ag_freestring, '', 20,30, false);
echo '</td>';



echo '<td valign="middle">';
html_print_submit_button (__('Show'), "uptbutton", false, 'class="sub search"');
echo "</td>";



echo "<tr>";
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
		$order = array('field' => 'tagente.nombre',
			'order' => 'ASC');
		break;
}

switch ($config["dbtype"]) {
	case "mysql":
		$sql = "SELECT
			(SELECT GROUP_CONCAT(ttag.name SEPARATOR ',')
				FROM ttag
				WHERE ttag.id_tag IN (
					SELECT ttag_module.id_tag
					FROM ttag_module
					WHERE ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo))
			AS tags, 
			tagente_modulo.id_agente_modulo,
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
			tagente_modulo.critical_inverse,
			tagente_modulo.warning_inverse,
			tagente_modulo.critical_instructions,
			tagente_modulo.warning_instructions,
			tagente_modulo.unknown_instructions,
			tagente_estado.utimestamp AS utimestamp" .
			$sql_from . $sql_conditions_all . "
			ORDER BY " . $order['field'] . " " . $order['order'] . "
			LIMIT ".$offset.",".$limit_sql;
		break;
	case "postgresql":
		$sql = "SELECT
			(SELECT  STRING_AGG(ttag.name, ',')
				FROM ttag
				WHERE ttag.id_tag IN (
					SELECT ttag_module.id_tag
					FROM ttag_module
					WHERE ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo))
			AS tags,
			tagente_modulo.id_agente_modulo,
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
			tagente_modulo.min_critical,
			tagente_modulo.unit,
			tagente_modulo.max_critical,
			tagente_modulo.str_critical,
			tagente_modulo.extended_info,
			tagente_modulo.critical_inverse,
			tagente_modulo.warning_inverse,
			tagente_modulo.critical_instructions,
			tagente_modulo.warning_instructions,
			tagente_modulo.unknown_instructions,
			tagente_estado.utimestamp AS utimestamp".$sql_form . $sql_conditions_all." LIMIT " . $limit_sql . " OFFSET " . $offset;
		break;
	case "oracle":
		$set = array();
		$set['limit'] = $limit_sql;
		$set['offset'] = $offset;
		$sql = "SELECT
			(SELECT  wmsys.wm_concat(ttag.name)
				FROM ttag
				WHERE ttag.id_tag IN (
					SELECT ttag_module.id_tag
					FROM ttag_module
					WHERE ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo))
			AS tags,
			tagente_modulo.id_agente_modulo,
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
			tagente_modulo.critical_inverse,
			tagente_modulo.warning_inverse,
			tagente_modulo.critical_instructions,
			tagente_modulo.warning_instructions,
			tagente_modulo.unknown_instructions,
			tagente_estado.utimestamp AS utimestamp" . $sql_form . $sql_conditions_all;
		$sql = oracle_recode_query ($sql, $set);
		break;
}

if (! defined ('METACONSOLE')) {
	$result = db_get_all_rows_sql ($sql);
	
	if ($count > $config["block_size"]) {
		ui_pagination ($count, false, $offset);
	}
	
	if ($result === false) {
		$result = array ();
	}
}
else {
	// For each server defined and not disabled:
	$servers = db_get_all_rows_sql ("SELECT *
		FROM tmetaconsole_setup
		WHERE disabled = 0");
	if ($servers === false)
		$servers = array();
	
	$result = array();
	$count_modules = 0;
	foreach($servers as $server) {
		// If connection was good then retrieve all data server
		if (metaconsole_connect($server) == NOERR) {
			$connection = true;
		}
		else {
			$connection = false;
		}
		 
		$result_server = db_get_all_rows_sql ($sql);
		
		if (!empty($result_server)) {
			
			// Create HASH login info
			$pwd = $server["auth_token"];
			$auth_serialized = json_decode($pwd,true);
			
			if (is_array($auth_serialized)) {
				$pwd = $auth_serialized["auth_token"];
				$api_password = $auth_serialized["api_password"];
				$console_user = $auth_serialized["console_user"];
				$console_password = $auth_serialized["console_password"];
			}
			
			$user = $config["id_user"];
			$hashdata = $user.$pwd;
			$hashdata = md5($hashdata);
			$url_hash = "&loginhash=auto&loginhash_data=$hashdata&loginhash_user=$user";
			
			foreach ($result_server as $result_element_key => $result_element_value) {
				
				$result_server[$result_element_key]['server_name'] = $server["server_name"];
				$result_server[$result_element_key]['server_url'] = $server["server_url"]."/";
				$result_server[$result_element_key]['hashdata'] = $hashdata;
				$result_server[$result_element_key]['user'] = $config["id_user"];
				
				$count_modules++;
				
			}
			
			$result = array_merge($result, $result_server);
		}
		
		metaconsole_restore_db();
		
	}
	
	if ($count_modules > $config["block_size"]) {
		ui_pagination ($count_modules, false, $offset);
	}
	
	// Get number of elements of the pagination
	$result = ui_meta_get_subset_array($result, $inferior_limit, $superior_limit);
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

$table->head[1] = __('Agent'); 
if (! defined ('METACONSOLE')) {
	$table->head[1] .=' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=agent_name&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectAgentNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=agent_name&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectAgentNameDown, "alt" => "down")) . '</a>';
}

$table->head[2] = __('Type');
if (! defined ('METACONSOLE')) {
	$table->head[2] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=type&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectTypeUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=type&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectTypeDown, "alt" => "down")) . '</a>';
}
$table->align[2] = "left";

$table->head[3] = __('Module name'); 
if (! defined ('METACONSOLE')) {
	$table->head[3] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=module_name&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectModuleNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=module_name&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectModuleNameDown, "alt" => "down")) . '</a>';
}

/*
$table->head[4] = __('Tags');
*/

$table->head[5] = __('Interval'); 
if (! defined ('METACONSOLE')) {
	$table->head[5] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=interval&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectIntervalUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=interval&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectIntervalDown, "alt" => "down")) . '</a>';
}

$table->align[5] = "center";

$table->head[6] = __('Status');
if (! defined ('METACONSOLE')) {
	$table->head[6] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=status&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectStatusUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=status&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectStatusDown, "alt" => "down")) . '</a>';
}

$table->align[6] = "center";

$table->head[7] = __('Graph');
$table->align[7] = "center";

$table->head[8] = __('Warn');
$table->align[8] = "left";

$table->head[9] = __('Data');
if (! defined ('METACONSOLE')) {
	$table->head[9] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=data&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectDataUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=data&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectDataDown, "alt" => "down")) . '</a>';
}

$table->align[9] = "left";

$table->head[10] = __('Timestamp');
if (! defined ('METACONSOLE')) {
	 $table->head[10] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=timestamp&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectTimestampUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . '&amp;sort_field=timestamp&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectTimestampDown, "alt" => "down")) . '</a>';
}

$table->align[10] = "right";

$rowPair = true;
$iterator = 0;

$id_type_web_content_string = db_get_value('id_tipo', 'ttipo_modulo',
	'nombre', 'web_content_string');

foreach ($result as $row) {
	$is_web_content_string = (bool)db_get_value_filter('id_agente_modulo',
		'tagente_modulo',
		array('id_agente_modulo' => $row['id_agente_modulo'],
			'id_tipo_modulo' => $id_type_web_content_string));
	
	//Fixed the goliat sends the strings from web
	//without HTML entities
	if ($is_web_content_string) {
		$row['datos'] = io_safe_input($row['datos']);
	}
	
	//Fixed the data from Selenium Plugin
	if ($row['datos'] != strip_tags($row['datos'])) {
		$row['datos'] = io_safe_input($row['datos']);
	}
	
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
	
	if (defined('METACONSOLE')) {
		$data[1] = '<strong><a href="'. $row["server_url"] .'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='. $row["id_agent"] . '&amp;loginhash=auto&amp;loginhash_data=' . $row["hashdata"] . '&amp;loginhash_user=' . $row["user"] . '">'; 
		$data[1] .= ui_print_truncate_text($row["agent_name"], 'agent_small', false, true, false, '[&hellip;]', 'font-size:7.5pt;');
		$data[1] .= '</a></strong>';
	}
	else {
		$data[1] = '<strong><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$row["id_agent"].'">';
		$data[1] .= ui_print_truncate_text($row["agent_name"], 'agent_medium', false, true, false, '[&hellip;]', 'font-size:7.5pt;');
		$data[1] .= '</a></strong>';
	}
	
	$data[2] = html_print_image("images/" . modules_show_icon_type ($row["module_type"]), true); 
	
	$data[3] = ui_print_truncate_text($row["module_name"], 'agent_small', false, true, true);
	if ($row["extended_info"] != "") {
		$data[3] .= ui_print_help_tip ($row["extended_info"], true, '/images/comments.png');
	}
	if ($row["tags"] != "") {
		$data[3] .= ui_print_help_tip ($row["tags"], true, '/images/tip.png');
	}
	
/*
	$data[4] = ui_print_truncate_text($row['tags'], 'agent_small', false, true, true, '[&hellip;]', 'font-size:7pt;');
*/
	
	$data[5] = ($row['module_interval'] == 0) ? human_time_description_raw($row['agent_interval']) : human_time_description_raw($row['module_interval']);
	
	if ($row['utimestamp'] == 0 && (($row['module_type'] < 21 ||
		$row['module_type'] > 23) && $row['module_type'] != 100)) {
		$data[6] = ui_print_status_image(STATUS_MODULE_NO_DATA,
			__('NOT INIT'), true);
	}
	elseif ($row["estado"] == 0) {
		$data[6] = ui_print_status_image(STATUS_MODULE_OK,
			__('NORMAL') . ": " . $row["datos"], true);
	}
	elseif ($row["estado"] == 1) {
		$data[6] = ui_print_status_image(STATUS_MODULE_CRITICAL,
			__('CRITICAL') . ": " . $row["datos"], true);
	}
	elseif ($row["estado"] == 2) {
		$data[6] = ui_print_status_image(STATUS_MODULE_WARNING,
			__('WARNING') . ": " . $row["datos"], true);
	}
	else {
		$last_status =  modules_get_agentmodule_last_status(
			$row['id_agente_modulo']);
		switch($last_status) {
			case 0:
				$data[6] = ui_print_status_image(STATUS_MODULE_UNKNOWN,
					__('UNKNOWN') . " - " . __('Last status') . " " .
					__('NORMAL') . ": " . $row["datos"], true);
				break;
			case 1:
				$data[6] = ui_print_status_image(STATUS_MODULE_UNKNOWN,
					__('UNKNOWN') . " - " . __('Last status') ." " .
					__('CRITICAL') . ": " . $row["datos"], true);
				break;
			case 2:
				$data[6] = ui_print_status_image(STATUS_MODULE_UNKNOWN,
					__('UNKNOWN') . " - " . __('Last status') . " " .
					__('WARNING') . ": " . $row["datos"], true);
				break;
		}
	}
	
	$data[7] = "";
	
	if ($row['history_data'] == 1) {
		
		$graph_type = return_graphtype ($row["module_type"]);
		
		$nombre_tipo_modulo = modules_get_moduletype_name ($row["module_type"]);
		$handle = "stat".$nombre_tipo_modulo."_".$row["id_agente_modulo"];
		$url = 'include/procesos.php?agente='.$row["id_agente_modulo"];
		$win_handle=dechex(crc32($row["id_agente_modulo"].$row["module_name"]));
		
		if (defined('METACONSOLE'))
			$link ="winopeng('" . $row['server_url'] . "operation/agentes/stat_win.php?type=$graph_type&period=86400&loginhash=auto&loginhash_data=" . $row["hashdata"] . "&loginhash_user=" . $row["user"] . "&id=".$row["id_agente_modulo"]."&label=".base64_encode($row["module_name"])."&refresh=600','day_".$win_handle."')";
		else
			$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&period=86400&id=".$row["id_agente_modulo"]."&label=".base64_encode($row["module_name"])."&refresh=600','day_".$win_handle."')";
		
		$data[7] = '<a href="javascript:'.$link.'">' . html_print_image("images/chart_curve.png", true, array("border" => '0', "alt" => "")) .  '</a>';
		if (defined('METACONSOLE'))
			//$data[7] .= "&nbsp;<a href='" . $row['server_url'] . "index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=".$row["id_agent"]."&amp;tab=data_view&period=86400&loginhash=auto&loginhash_data=" . $row["hashdata"] . "&loginhash_user=" . $row["user"] . "&amp;id=".$row["id_agente_modulo"]."'>" . html_print_image('images/binary.png', true, array("style" => '0', "alt" => '')) . "</a>";
			$data[7] .= "<a href='javascript: show_module_detail_dialog(" . $row["id_agente_modulo"] . ", ". $row['id_agent'].", \"" . $row['server_name'] . "\", 0, 86400)'>". html_print_image ("images/binary.png", true, array ("border" => "0", "alt" => "")) . "</a>";
		else
			$data[7] .= "&nbsp;<a href='index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=".$row["id_agent"]."&amp;tab=data_view&period=86400&amp;id=".$row["id_agente_modulo"]."'>" . html_print_image('images/binary.png', true, array("style" => '0', "alt" => '')) . "</a>";
		
	}
	
	$data[8] = ui_print_module_warn_value($row['max_warning'], $row['min_warning'], $row['str_warning'], $row['max_critical'], $row['min_critical'], $row['str_critical']);
	
	if (is_numeric($row["datos"])) {
		$salida = format_numeric($row["datos"]);
		
		// Show units ONLY in numeric data types
		if (isset($row["unit"])) {
			$salida .= "&nbsp;" . '<i>'. io_safe_output($row["unit"]) . '</i>';
			$salida = ui_print_truncate_text($salida, 'agent_small', true, true, false, '[&hellip;]', 'font-size:7.5pt;');
		}
	}
	else {
		//Fixed the goliat sends the strings from web
		//without HTML entities
		if ($is_web_content_string) {
			$module_value = $row["datos"];
		}
		else {
			$module_value = io_safe_output($row["datos"]);
		}
		
		$sub_string = substr(io_safe_output($row["datos"]), 0, 12);
		if ($module_value == $sub_string) {
			$salida = $module_value;
		}
		else {
			//Fixed the goliat sends the strings from web
			//without HTML entities
			if ($is_web_content_string) {
				$sub_string = substr($row["datos"], 0, 12);
			}
			else {
				//Fixed the data from Selenium Plugin
				if ($module_value != strip_tags($module_value)) {
					$module_value = io_safe_input($module_value);
					$sub_string = substr($row["datos"], 0, 12);
				}
				else {
					$sub_string = substr(io_safe_output($row["datos"]),0, 12);
				}
			}
			
			
			
			if ($module_value == $sub_string) {
				$salida = $module_value;
			}
			else {
				$salida = "<span " .
					"id='hidden_value_module_" . $row["id_agente_modulo"] . "'
					style='display: none;'>" .
					$module_value .
					"</span>" . 
					"<span " .
					"id='value_module_" . $row["id_agente_modulo"] . "'
					title='" . $module_value . "' " .
					"style='white-space: nowrap;'>" . 
					'<span id="value_module_text_' . $row["id_agente_modulo"] . '">' .
						$sub_string . '</span> ' .
					"<a href='javascript: toggle_full_value(" . $row["id_agente_modulo"] . ")'>" .
						html_print_image("images/rosette.png", true) . "</a>" . "</span>";
			}
		}
	}
	
	$data[9] = $salida;
	
	if ($row["module_interval"] > 0)
		$interval = $row["module_interval"];
	else
		$interval = $row["agent_interval"];
	
	if ($row['estado'] == 3) {
		$option = array ("html_attr" => 'class="redb"',"style" => 'font-size:7pt;');
	}
	else {
		$option = array ("style" => 'font-size:7pt;');
	}
	$data[10] = ui_print_timestamp ($row["utimestamp"], true, $option);
	
	array_push ($table->data, $data);
}
if (!empty ($table->data)) {
	html_print_table ($table);
}
else {
	echo "<div class='nf'>".__('This group doesn\'t have any monitor')."</div>";
}

echo "<div id='monitor_details_window'></div>";
ui_require_javascript_file('pandora_modules');

?>
<script type="text/javascript">
	function toggle_full_value(id) {
		text = $("#hidden_value_module_" + id).html();
		old_text = $("#value_module_text_" + id).html();
		
		$("#hidden_value_module_" + id).html(old_text);
		
		$("#value_module_text_" + id).html(text);
	}
	
	// Show the modal window of an module
	function show_module_detail_dialog(module_id, id_agent, server_name, offset, period) {
		if (period == -1) {
			period = $('#period').val();
		}
		
		$.ajax({
			type: "POST",
			url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
			data: "page=include/ajax/module&get_module_detail=1&server_name="+server_name+"&id_agent="+id_agent+"&id_module=" + module_id+"&offset="+offset+"&period="+period,
			dataType: "html",
			success: function(data) {
				$("#monitor_details_window").hide ()
					.empty ()
					.append (data)
					.dialog ({
						resizable: true,
						draggable: true,
						modal: true,
						overlay: {
							opacity: 0.5,
							background: "black"
						},
						width: 620,
						height: 500
					})
					.show ();
				
				refresh_pagination_callback (module_id, id_agent, server_name);
			}
		});
	}
	
	function refresh_pagination_callback (module_id, id_agent, server_name) {
		$(".pagination").click( function() {
			var classes = $(this).attr('class');
			classes = classes.split(' ');
			var offset_class = classes[1];
			offset_class = offset_class.split('_');
			var offset = offset_class[1];
		
			var period = $('#period').val();
			
			show_module_detail_dialog(module_id, id_agent, server_name, offset, period);
			
			return false;
		});
	}
</script>
