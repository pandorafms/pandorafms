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

if (! check_acl ($config['id_user'], 0, 'AR')
	&& ! check_acl ($config['id_user'], 0, 'AW') && 
		! check_acl ($config['id_user'], 0, 'AM')) {
	db_pandora_audit('ACL Violation',
		'Trying to access Agent Management');
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
	ui_print_page_header (__('Monitor detail'),
		'images/brick.png', false);
}
else {
	
	ui_meta_print_header(__('Monitor view'));
}

$ag_freestring 		= 		get_parameter ('ag_freestring');
$moduletype 		= 		(string) get_parameter ('moduletype');
$datatype 		= 		(string) get_parameter ('datatype');
$ag_modulename 		= 		(string) get_parameter ('ag_modulename');
$refr 				= 		(int) get_parameter('refr', 0);
$offset 			= 		(int) get_parameter ('offset', 0);
$status 			= 		(int) get_parameter ('status', 4);
$modulegroup 		= 		(int) get_parameter ('modulegroup', -1);
$tag_filter 		= 		(int) get_parameter('tag_filter', 0);
// Sort functionality
$sortField 			= 		get_parameter('sort_field');
$sort 				= 		get_parameter('sort', 'none');
//When the previous page was a visualmap and show only one module
$id_module 			= 		(int) get_parameter('id_module', 0);
$ag_custom_fields 	= 		(array) get_parameter('ag_custom_fields', array());

if (!is_metaconsole()) {
	$ag_group 		= 		(int) get_parameter ('ag_group', 0);
}
else {
	$ag_group 		= 		get_parameter ('ag_group', 0);
	$ag_group_metaconsole = $ag_group;
}

$ag_custom_fields_params = '';
if (!empty($ag_custom_fields)) {
	foreach ($ag_custom_fields as $id => $value) {
		if (!empty($value))
			$ag_custom_fields_params .= 
				'&ag_custom_fields[' . $id . ']=' . $value;
	}
}
if ($id_module) {
	$status = -1;
	$ag_modulename = modules_get_agentmodule_name($id_module);
	$ag_freestring = modules_get_agentmodule_agent_name($id_module);
}

enterprise_hook('open_meta_frame');

// Get Groups and profiles from user
$user_groups = implode (',', array_keys (users_get_groups ()));

////////////////////////////////////
// Begin Build SQL sentences
$sql_from = ' FROM ttipo_modulo,tagente, tagente_modulo, tagente_estado,tmodule ';

$sql_conditions_base = ' WHERE tagente.id_agente = tagente_modulo.id_agente 
		AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.id_tipo_modulo = ttipo_modulo.id_tipo AND tmodule.id_module = tagente_modulo.id_modulo';

$sql_conditions = ' AND tagente_modulo.disabled = 0 AND tagente.disabled = 0';

if (is_numeric($ag_group)) {
	$id_ag_group = 0;
}
else {
	$id_ag_group = db_get_value('id_grupo', 'tgrupo', 'nombre', $ag_group);
}

// Agent group selector
if (!is_metaconsole()) {
	if ($ag_group > 0 && check_acl ($config['id_user'], $ag_group, 'AR')) {
		$sql_conditions_group = sprintf (' AND tagente.id_grupo = %d', $ag_group);
	}
	elseif ($user_groups != '') {
		// User has explicit permission on group 1 ?
		$sql_conditions_group = ' AND tagente.id_grupo IN ('.$user_groups.')';
	}
}
else {
	if (((int)$ag_group !== 0) && (check_acl ($config['id_user'], $id_ag_group, 'AR'))) {
		$sql_conditions_group = sprintf (' AND tagente.id_grupo IN (%s) ', $ag_group);
	}
	elseif ($user_groups != '') {
		// User has explicit permission on group 1 ?
		$sql_conditions_group = ' AND tagente.id_grupo IN ('.$user_groups.')';
	}
}

// Module group
if (is_metaconsole()) {
	if ($modulegroup != '-1')
		$sql_conditions .= sprintf (' AND tagente_modulo.id_module_group IN (%s)', $modulegroup);
}
else if ($modulegroup > -1) {
	$sql_conditions .= sprintf (' AND tagente_modulo.id_module_group = \'%d\'', $modulegroup);

}

// Module name selector
if ($ag_modulename != '') {
	$sql_conditions .= sprintf (' AND tagente_modulo.nombre = \'%s\'',
		$ag_modulename);
}


if ($datatype != '') {
	$sql_conditions .= sprintf (' AND ttipo_modulo.id_tipo =' .$datatype);
}

if ($moduletype != '') {
	$sql_conditions .= sprintf (' AND tagente_modulo.id_modulo =' .$moduletype);
}


// Freestring selector
if ($ag_freestring != '') {
	$sql_conditions .= sprintf (' AND (tagente.nombre LIKE \'%%%s%%\'
		OR tagente_modulo.nombre LIKE \'%%%s%%\'
		OR tagente_modulo.descripcion LIKE \'%%%s%%\')',
		$ag_freestring, $ag_freestring, $ag_freestring);
}

// Status selector
if ($status == AGENT_MODULE_STATUS_NORMAL) { //Normal
	$sql_conditions .= ' AND tagente_estado.estado = 0 
	AND (utimestamp > 0 OR (tagente_modulo.id_tipo_modulo IN(21,22,23,100))) ';
}
elseif ($status == AGENT_MODULE_STATUS_CRITICAL_BAD) { //Critical
	$sql_conditions .= ' AND tagente_estado.estado = 1 AND utimestamp > 0';
}
elseif ($status == AGENT_MODULE_STATUS_WARNING) { //Warning
	$sql_conditions .= ' AND tagente_estado.estado = 2 AND utimestamp > 0';
}
elseif ($status == AGENT_MODULE_STATUS_NOT_NORMAL) { //Not normal
	$sql_conditions .= ' AND tagente_estado.estado <> 0';
} 
elseif ($status == AGENT_MODULE_STATUS_UNKNOWN) { //Unknown
	$sql_conditions .= ' AND tagente_estado.estado = 3 AND tagente_estado.utimestamp <> 0';
}
elseif ($status == AGENT_MODULE_STATUS_NOT_INIT) { //Not init
	$sql_conditions .= ' AND tagente_estado.utimestamp = 0
		AND tagente_modulo.id_tipo_modulo NOT IN (21,22,23,100)';
}

// Filter by agent custom fields
$sql_conditions_custom_fields = "";
if (!empty($ag_custom_fields)) {

	$cf_filter = array();
	foreach ($ag_custom_fields as $field_id => $value) {
		if (!empty($value)) {
			$cf_filter[] = '(tagent_custom_data.id_field = ' . $field_id .
				' AND tagent_custom_data.description LIKE \'%'.$value.'%\')';
		}
	}
	if (!empty($cf_filter)) {
		$sql_conditions_custom_fields = ' AND tagente.id_agente IN (
				SELECT tagent_custom_data.id_agent
				FROM tagent_custom_data
				WHERE ' . implode(' AND ', $cf_filter) . ')';
	}
}

//Filter by tag
if ($tag_filter !== 0) {
	if (is_metaconsole()) {
		$sql_conditions .= ' AND tagente_modulo.id_agente_modulo IN (
				SELECT ttag_module.id_agente_modulo
				FROM ttag_module
				WHERE ttag_module.id_tag IN ('.$tag_filter.'))';
	}
	else {
		$sql_conditions .= ' AND tagente_modulo.id_agente_modulo IN (
				SELECT ttag_module.id_agente_modulo
				FROM ttag_module
				WHERE ttag_module.id_tag = ' . $tag_filter . ')';
	}
}



// Apply the module ACL with tags
$sql_conditions_tags = '';

if (!users_is_admin()) {
	if ($ag_group !== 0) {
		$sql_conditions_tags = tags_get_acl_tags($config['id_user'],
			$ag_group, 'AR', 'module_condition', 'AND', 'tagente_modulo',
			true, array(), false);
	} else {
		// Fix: for tag functionality groups have to be all user_groups (propagate ACL funct!)
		$groups = users_get_groups($config['id_user']);
		$sql_conditions_tags = tags_get_acl_tags(
			$config['id_user'], array_keys($groups), 'AR',
			'module_condition', 'AND', 'tagente_modulo', true, array(),
			false);
	}
	if (is_numeric($sql_conditions_tags)) {
		$sql_conditions_tags = ' AND 1 = 0';
	}
}

// Two modes of filter. All the filters and only ACLs filter
$sql_conditions_all = $sql_conditions_base . $sql_conditions . $sql_conditions_group . $sql_conditions_tags . $sql_conditions_custom_fields;
$sql_conditions_acl = $sql_conditions_base . $sql_conditions_group . $sql_conditions_tags . $sql_conditions_custom_fields;

// Get count to paginate
if (!defined('METACONSOLE')) 
	$count = db_get_sql ('SELECT COUNT(tagente_modulo.id_agente_modulo) ' . $sql_from . $sql_conditions_all);

// Get limit_sql depend of the metaconsole or standard mode
if (is_metaconsole()) {
	// Offset will be used to get the subset of modules
	$inferior_limit = $offset;
	$superior_limit = $config['block_size'] + $offset;
	// Offset reset to get all elements
	$offset = 0;
	if (!isset($config['meta_num_elements']))
		$config['meta_num_elements'] = 100;
	
	$limit_sql = $config['meta_num_elements'];
}
else
	$limit_sql = $config['block_size'];

// End Build SQL sentences
/////////////////////////////////////

// Start Build Search Form
/////////////////////////////////////
$table = new StdClass();
$table->width = '100%';
$table->cellspacing = 0;
$table->cellpadding = 0;
$table->class = 'databox filters';
$table->style[0] = 'font-weight: bold;';
$table->style[1] = 'font-weight: bold;';
$table->style[2] = 'font-weight: bold;';
$table->style[3] = 'font-weight: bold;';
$table->style[4] = 'font-weight: bold;';

$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select_groups($config['id_user'], 'AR',
	true, 'ag_group', $ag_group, '',  '', '0', true, false, 
		false, 'w130', false, 'width:150px;', false, false,
		'id_grupo', false);

$fields = array ();
$fields[AGENT_MODULE_STATUS_NORMAL] = __('Normal');
$fields[AGENT_MODULE_STATUS_WARNING] = __('Warning');
$fields[AGENT_MODULE_STATUS_CRITICAL_BAD] = __('Critical');
$fields[AGENT_MODULE_STATUS_UNKNOWN] = __('Unknown');
$fields[AGENT_MODULE_STATUS_NOT_NORMAL] = __('Not normal'); //default
$fields[AGENT_MODULE_STATUS_NOT_INIT] = __('Not init');

$table->data[0][2] = __('Monitor status');
$table->data[0][3] = html_print_select ($fields, 'status', $status, '', __('All'), -1,
	true, false, true, '', false, 'width: 150px;');

$rows_select = array();
$table->data[0][4] = __('Module group');
$rows_select[0] = __('Not assigned');
if (!is_metaconsole()) {
	$rows = db_get_all_rows_sql('SELECT *
		FROM tmodule_group ORDER BY name');
	$rows = io_safe_output($rows);
	if (!empty($rows))
		foreach ($rows as $module_group)
			$rows_select[$module_group['id_mg']] = $module_group['name'];
}
else {
	$rows_select = modules_get_modulegroups();
}
$table->data[0][5] = html_print_select($rows_select, 'modulegroup', $modulegroup, '', __('All'), -1, true, false, true, '', false, 'width: 120px;');

$table->rowspan[0][6] = 2;
$table->data[0][6] = html_print_submit_button (__('Show'), 'uptbutton',
						false, 'class="sub search" style="margin-top:0px;"',true);
$modules = array();
$modules = modules_get_modules_name ($sql_from , $sql_conditions_acl, is_metaconsole());

$table->data[1][0] = __('Module name');
$table->data[1][1] = html_print_select (index_array ($modules, 'nombre', 'nombre'), 'ag_modulename',
	$ag_modulename, '', __('All'), '', true, false, true, '', false, 'width: 150px;');

$table->data[1][2] = __('Search');
$table->data[1][3] = html_print_input_text ('ag_freestring', $ag_freestring, '', 20, 30, true);

if (!is_metaconsole())
	$table->data[1][4] = __('Tags') .
		ui_print_help_tip(__('Only it is show tags in use.'), true);
else
	$table->data[1][4] = __('Tags') .
		ui_print_help_tip(__('Only it is show tags in use.'), true);

$tags = array();
$tags = tags_get_user_tags();
if (empty($tags)) {
	$table->data[1][5] = __('No tags');
}
else {
	$table->data[1][5] = html_print_select ($tags, 'tag_filter',
		$tag_filter, '', __('All'), '', true, false, true, '', false, 'width: 150px;');
}


  
  $network_available = db_get_sql ("SELECT count(*)
    FROM tserver
    WHERE server_type = 1"); //POSTGRESQL AND ORACLE COMPATIBLE
  $wmi_available = db_get_sql ("SELECT count(*)
    FROM tserver
    WHERE server_type = 6"); //POSTGRESQL AND ORACLE COMPATIBLE
  $plugin_available = db_get_sql ("SELECT count(*)
    FROM tserver
    WHERE server_type = 4"); //POSTGRESQL AND ORACLE COMPATIBLE
  $prediction_available = db_get_sql ("SELECT count(*)
    FROM tserver
    WHERE server_type = 5"); //POSTGRESQL AND ORACLE COMPATIBLE

  // Development mode to use all servers
  if ($develop_bypass) {
    $network_available = 1;
    $wmi_available = 1;
    $plugin_available = 1;
    $prediction_available = 1;
  }

  $typemodules = array ();
  $typemodules[1] = __('Data server module');
  if ($network_available)
    $typemodules[2] = __('Network server module');
  if ($plugin_available)
    $typemodules[4] = __('Plugin server module');
  if ($wmi_available)
    $typemodules[6] = __('WMI server module');
  if ($prediction_available)
    $typemodules[5] = __('Prediction server module');
  if (enterprise_installed()) {
      $typemodules[7] = __('Web server module');
    }
    

  $table->data[2][0] = '<span>'.__('Server type').'</span>';

  $table->data[2][1] = html_print_select ($typemodules, 'moduletype',$moduletype, '', __('All'),'', true, false, true, '', false, 'width: 150px;');
	
	
	$table->data[2][2] = '<span id="datatypetittle" ';
	
	if(!$_GET['sort']){
	$table->data[2][2] .= 'style="display:none"';
}

  $table->data[2][2] .= '>'.__('Data type').'</span>';
		
		
	$table->data[2][3] .='<div id="datatypebox">';
		
		
	switch ($moduletype) 
			{
			case 1:
			$sql = sprintf ('SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria IN (6,7,8,0,1,2,-1) order by descripcion ');
				break;
			case 2:
			$sql = sprintf ('SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria between 3 and 5 ');
				break;
			case 4:
			$sql = sprintf ('SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria between 0 and 2 ');
				break;
			case 6:
			$sql = sprintf ('SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria between 0 and 2 ');
				break;
			case 7:
			$sql = sprintf ('SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria = 9');
				break;
			case 5:
			$sql = sprintf ('SELECT id_tipo, descripcion
				FROM ttipo_modulo
				WHERE categoria = 0');
				break;
			case '':
				$sql = sprintf ('SELECT id_tipo, descripcion
					FROM ttipo_modulo');
					break;
					
			}
			$a = db_get_all_rows_sql($sql);
			$table->data[2][3] .= '<select id="datatype" name="datatype" ';
			
			if(!$_GET['sort']){
			$table->data[2][3] .= 'style="display:none"';
		}
		
		$table->data[2][3] .= '>';
			
			$table->data[2][3] .= '<option name="datatype" value="">'.__("All").'</option>';
			
			
			foreach ($a as $valor) {
				
				$table->data[2][3] .= '<option name="datatype" value="'.$valor['id_tipo'].'" ';
				
				if($valor['id_tipo'] == $datatype){
					$table->data[2][3] .= 'selected';
				}
				
				$table->data[2][3] .= '>'.$valor['descripcion'].'</option>';
			}
			$table->data[2][3] .= '</select>';
		
		
		
		
		$table->data[2][3] .= '</div>';


$table_custom_fields = new stdClass();
$table_custom_fields->class = 'filters';
$table_custom_fields->width = '100%';

if (is_metaconsole()) {
	$table_custom_fields->styleTable = 'margin-left:0px; margin-top:15px;';
	$table_custom_fields->cellpadding = '0';
	$table_custom_fields->cellspacing = '0';
}
$table_custom_fields->style = array();
if(!is_metaconsole())
	$table_custom_fields->style[0] = 'font-weight: bold; width: 150px;';
else
	$table_custom_fields->style[0] = 'font-weight: bold;';
$table_custom_fields->colspan = array();
$table_custom_fields->data = array();

$custom_fields = db_get_all_fields_in_table('tagent_custom_fields');
if ($custom_fields === false) $custom_fields = array();

foreach ($custom_fields as $custom_field) {
	$row = array();
	$row[0] = $custom_field['name'];
	
	$custom_field_value = "";
	if (!empty($ag_custom_fields)) {
		$custom_field_value = $ag_custom_fields[$custom_field['id_field']];
		if (empty($custom_field_value)) {
			$custom_field_value = "";
		}
	}
	$row[1] = html_print_input_text ("ag_custom_fields[".$custom_field['id_field']."]", $custom_field_value, '', 100, 300, true);
	
	$table_custom_fields->data[] = $row;
}


$filters = '<form method="post" action="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;ag_group=' . 
		$ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;moduletype=' . $moduletype . '&amp;datatype=' . $datatype . '&amp;status=' . $status . '&amp;sort_field=' . 
			$sortField . '&amp;sort=' . $sort .'&amp;pure=' . $config['pure'] . $ag_custom_fields_params . '">';
if (is_metaconsole()) {
	$table->colspan[3][0] = 7;
	$table->cellstyle[3][0] = 'padding: 10px;';
	$table->data[3][0] = ui_toggle(
		html_print_table($table_custom_fields, true),
		__('Advanced Options'), '', true, true);
	
	$filters .= html_print_table($table, true);
	$filters .= "</form>";
	ui_toggle($filters, __('Show Options'));
}
else {
	$table->colspan[3][0] = 7;
	$table->cellstyle[3][0] = 'padding-left: 10px;';
	$table->data[3][0] = ui_toggle(html_print_table($table_custom_fields, 
		true), __('Agent custom fields'), '', true, true);
	
	$filters .= html_print_table($table, true);
	$filters .= '</form>';
	echo $filters;
}
unset($table);
// End Build Search Form
/////////////////////////////////////

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
				$order = array('field' => 'tagente.nombre',
					'order' => 'ASC');
				break;
			case 'down':
				$selectAgentNameDown = $selected;
				$order = array('field' => 'tagente.nombre',
					'order' => 'DESC');
				break;
		}
		break;
	case 'type':
		switch ($sort) {
			case 'up':
				$selectTypeUp = $selected;
				$order = array(
					'field' => 'tagente_modulo.id_tipo_modulo',
					'order' => 'ASC');
				break;
			case 'down':
				$selectTypeDown = $selected;
				$order = array(
					'field' => 'tagente_modulo.id_tipo_modulo',
					'order' => 'DESC');
				break;
		}
		break;
		case 'moduletype':
			switch ($sort) {
				case 'up':
					$selectTypeUp = $selected;
					$order = array(
						'field' => 'tagente_modulo.id_modulo',
						'order' => 'ASC');
					break;
				case 'down':
					$selectTypeDown = $selected;
					$order = array(
						'field' => 'tagente_modulo.id_modulo',
						'order' => 'DESC');
					break;
			}
			break;
	case 'module_name':
		switch ($sort) {
			case 'up':
				$selectModuleNameUp = $selected;
				$order = array(
					'field' => 'tagente_modulo.nombre',
					'order' => 'ASC');
				break;
			case 'down':
				$selectModuleNameDown = $selected;
				$order = array(
					'field' => 'tagente_modulo.nombre',
					'order' => 'DESC');
				break;
		}
		break;
	case 'interval':
		switch ($sort) {
			case 'up':
				$selectIntervalUp = $selected;
				$order = array(
					'field' => 'tagente_modulo.module_interval',
					'order' => 'ASC');
				break;
			case 'down':
				$selectIntervalDown = $selected;
				$order = array(
					'field' => 'tagente_modulo.module_interval',
					'order' => 'DESC');
				break;
		}
		break;
	case 'status':
		switch ($sort) {
			case 'up':
				$selectStatusUp = $selected;
				$order = array(
					'field' => 'tagente_estado.estado',
					'order' => 'ASC');
				break;
			case 'down':
				$selectStatusDown = $selected;
				$order = array(
					'field' => 'tagente_estado.estado',
					'order' => 'DESC');
				break;
		}
		break;
	case 'timestamp':
		switch ($sort) {
			case 'up':
				$selectTimestampUp = $selected;
				$order = array(
					'field' => 'tagente_estado.utimestamp',
					'order' => 'ASC');
				break;
			case 'down':
				$selectTimestampDown = $selected;
				$order = array(
					'field' => 'tagente_estado.utimestamp',
					'order' => 'DESC');
				break;
		}
		break;
	case 'data':
		switch ($sort) {
			case 'up':
				$selectTimestampUp = $selected;
				$order = array(
					'field' => 'tagente_estado.datos',
					'order' => 'ASC');
				break;
			case 'down':
				$selectTimestampDown = $selected;
				$order = array(
					'field' => 'tagente_estado.datos',
					'order' => 'DESC');
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
		$order = array(
			'field' => 'tagente.nombre',
			'order' => 'ASC');
		break;
}

switch ($config['dbtype']) {
	case 'mysql':
		$sql = 'SELECT
			(SELECT GROUP_CONCAT(ttag.name SEPARATOR \',\')
				FROM ttag
				WHERE ttag.id_tag IN (
					SELECT ttag_module.id_tag
					FROM ttag_module
					WHERE ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo))
			AS tags, 
			tagente_modulo.id_agente_modulo,
			tagente_modulo.id_modulo,
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
			tagente_estado.utimestamp AS utimestamp' .
			$sql_from . $sql_conditions_all . '
			ORDER BY ' . $order['field'] . " " . $order['order'] . '
			LIMIT '.$offset.",".$limit_sql;
		break;
	case 'postgresql':
		if (strstr($config['dbversion'], "8.4") !== false) {
			$string_agg = 'array_to_string(array_agg(ttag.name), \',\')';
		}
		else {
			$string_agg = 'STRING_AGG(ttag.name, \',\')';
		}
		
		$sql = 'SELECT
			(SELECT  ' . $string_agg . '
				FROM ttag
				WHERE ttag.id_tag IN (
					SELECT ttag_module.id_tag
					FROM ttag_module
					WHERE ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo))
			AS tags,
			tagente_modulo.id_agente_modulo,
			tagente_modulo.id_modulo,
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
			tagente_estado.utimestamp AS utimestamp' .
			$sql_from .
			$sql_conditions_all .
			' LIMIT ' . $limit_sql . ' OFFSET ' . $offset;
		break;
	case 'oracle':
		$set = array();
		$set['limit'] = $limit_sql;
		$set['offset'] = $offset;
		$sql = 'SELECT
			(SELECT LISTAGG(ttag.name, \',\') WITHIN GROUP (ORDER BY ttag.name) 
				FROM ttag
				WHERE ttag.id_tag IN (
					SELECT ttag_module.id_tag
					FROM ttag_module
					WHERE ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo))
			AS tags,
			tagente_modulo.id_agente_modulo,
			tagente_modulo.id_modulo,
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
			tagente_estado.utimestamp AS utimestamp' .
			$sql_from .
			$sql_conditions_all;
		$sql = oracle_recode_query ($sql, $set);
		break;
}


if (! defined ('METACONSOLE')) {
	$result = db_get_all_rows_sql ($sql);
	
	if ($result === false) {
		$result = array ();
	}
	else
		ui_pagination ($count, false, $offset);
}
else {
	// For each server defined and not disabled:
	$servers = db_get_all_rows_sql ('SELECT *
		FROM tmetaconsole_setup
		WHERE disabled = 0');
	if ($servers === false)
		$servers = array();
	
	$result = array();
	$count_modules = 0;
	foreach ($servers as $server) {
		// If connection was good then retrieve all data server
		if (metaconsole_connect($server) == NOERR)
			$connection = true;
		else
			$connection = false;
		 
		$result_server = db_get_all_rows_sql ($sql);
		
		if (!empty($result_server)) {
			
			// Create HASH login info
			$pwd = $server['auth_token'];
			$auth_serialized = json_decode($pwd,true);
			
			if (is_array($auth_serialized)) {
				$pwd = $auth_serialized['auth_token'];
				$api_password = $auth_serialized['api_password'];
				$console_user = $auth_serialized['console_user'];
				$console_password = $auth_serialized['console_password'];
			}
			
			$user = $config['id_user'];
			$user_rot13 = str_rot13($config['id_user']);
			$hashdata = $user.$pwd;
			$hashdata = md5($hashdata);
			$url_hash = '&' .
				'loginhash=auto&' .
				'loginhash_data=' . $hashdata . '&' .
				'loginhash_user=' . $user_rot13;
			
			foreach ($result_server as $result_element_key => $result_element_value) {
				
				$result_server[$result_element_key]['server_id'] = $server['id'];
				$result_server[$result_element_key]['server_name'] = $server['server_name'];
				$result_server[$result_element_key]['server_url'] = $server['server_url'].'/';
				$result_server[$result_element_key]['hashdata'] = $hashdata;
				$result_server[$result_element_key]['user'] = $config['id_user'];
				
				$count_modules++;
				
			}
			
			$result = array_merge($result, $result_server);
		}
		metaconsole_restore_db();
	}
	
	if ($count_modules > $config['block_size']) {
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

// Start Build List Result
/////////////////////////////////////
if (!empty($result)) {
	$table = new StdClass();
	$table->cellpadding = 0;
	$table->cellspacing = 0;
	$table->width = '100%';
	$table->class = 'databox data';
	$table->head = array ();
	$table->data = array ();
	$table->size = array ();
	$table->align = array ();

	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK)
		$table->head[0] = '<span title=\'' . __('Policy') . '\'>' . __('P.') . '</span>';

	$table->head[1] = __('Agent');
	if (!is_metaconsole()) {
		$table->head[1] .=' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=agent_name&amp;sort=up">' . html_print_image('images/sort_up.png', true, array('style' => $selectAgentNameUp, 'alt' => 'up'))  . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=' . $refr . '&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=agent_name&amp;sort=down">' . html_print_image('images/sort_down.png', true, array('style' => $selectAgentNameDown, 'alt' => 'down')) . '</a>';
	}

	$table->head[2] = __('Data Type');
	if (!is_metaconsole()) {
		$table->head[2] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=type&amp;sort=up">' . html_print_image('images/sort_up.png', true, array('style' => $selectTypeUp, 'alt' => 'up'))  . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=type&amp;sort=down">' . html_print_image('images/sort_down.png', true, array('style' => $selectTypeDown, 'alt' => 'down')) . '</a>';
	}
	$table->align[2] = 'left';

	$table->head[3] = __('Module name');
	if (!is_metaconsole()) {
		$table->head[3] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=module_name&amp;sort=up">' . html_print_image('images/sort_up.png', true, array('style' => $selectModuleNameUp, 'alt' => 'up'))  . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=module_name&amp;sort=down">' . html_print_image('images/sort_down.png', true, array('style' => $selectModuleNameDown, 'alt' => 'down')) . '</a>';
	}
  
  $table->head[4] = __('Server type');
	if (!is_metaconsole()) {
		$table->head[4] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=moduletype&amp;sort=up">' . html_print_image('images/sort_up.png', true, array('style' => $selectModuleNameUp, 'alt' => 'up'))  . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=moduletype&amp;sort=down">' . html_print_image('images/sort_down.png', true, array('style' => $selectModuleNameDown, 'alt' => 'down')) . '</a>';
	}
  

	$table->head[5] = __('Interval');
	if (!is_metaconsole()) {
		$table->head[5] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=interval&amp;sort=up">' . html_print_image('images/sort_up.png', true, array('style' => $selectIntervalUp, 'alt' => 'up'))  . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=interval&amp;sort=down">' . html_print_image('images/sort_down.png', true, array('style' => $selectIntervalDown, 'alt' => 'down')) . '</a>';
		$table->align[5] = 'left';
	}

	$table->head[6] = __('Status');
	if (!is_metaconsole()) {
		$table->head[6] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=status&amp;sort=up">' . html_print_image('images/sort_up.png', true, array('style' => $selectStatusUp, 'alt' => 'up'))  . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=status&amp;sort=down">' . html_print_image('images/sort_down.png', true, array('style' => $selectStatusDown, 'alt' => 'down')) . '</a>';
	}

	$table->align[6] = 'left';

	$table->head[7] = __('Graph');
	$table->align[7] = 'left';

	$table->head[8] = __('Warn');
	$table->align[8] = 'left';

	$table->head[9] = __('Data');
	$table->align[9] = 'left';
	if ( is_metaconsole() ) {
	$table->head[9] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=data&amp;sort=up">' . html_print_image('images/sort_up.png', true, array('style' => $selectStatusUp, 'alt' => 'up'))  . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=data&amp;sort=down">' . html_print_image('images/sort_down.png', true, array('style' => $selectStatusDown, 'alt' => 'down')) . '</a>';
	}

	$table->head[10] = __('Timestamp');
	if (!is_metaconsole()) {
		 $table->head[10] .= ' <a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=timestamp&amp;sort=up">' . html_print_image('images/sort_up.png', true, array('style' => $selectTimestampUp, 'alt' => 'up'))  . '</a>' .
		'<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;datatype='.$datatype . '&amp;moduletype='.$moduletype . '&amp;refr=' . $refr . '&amp;modulegroup='.$modulegroup . '&amp;offset=' . $offset . '&amp;ag_group=' . $ag_group . '&amp;ag_freestring=' . $ag_freestring . '&amp;ag_modulename=' . $ag_modulename . '&amp;status=' . $status . $ag_custom_fields_params . '&amp;sort_field=timestamp&amp;sort=down">' . html_print_image('images/sort_down.png', true, array('style' => $selectTimestampDown, 'alt' => 'down')) . '</a>';
		$table->align[10] = 'left';
	}

	$id_type_web_content_string = db_get_value('id_tipo', 'ttipo_modulo',
		'nombre', 'web_content_string');

	foreach ($result as $row) {
		//Avoid unset, null and false value
		if (empty($row['server_name']))
			$row['server_name'] = "";
		
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
		
		// TODO: Calculate hash access before to use it more simply like other sections. I.E. Events view
		if (defined('METACONSOLE')) {
			$agent_link = '<a href="'.
				$row['server_url'] .'index.php?' .
					'sec=estado&amp;' .
					'sec2=operation/agentes/ver_agente&amp;' .
					'id_agente='. $row['id_agent'] . '&amp;' .
					'loginhash=auto&amp;' .
					'loginhash_data=' . $row['hashdata'] . '&amp;' .
					'loginhash_user=' . str_rot13($row['user']) . '">';
			$agent_name = ui_print_truncate_text($row['agent_name'],
				'agent_small', false, true, false, '[&hellip;]',
				'font-size:7.5pt;');
			if (can_user_access_node ()) {
				$data[1] = $agent_link . '<b>' . $agent_name . '</b></a>';
			}
			else {
				$data[1] = $agent_name;
			}
		}
		else {
			$data[1] = '<strong><a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$row['id_agent'].'">';
			$data[1] .= ui_print_truncate_text($row['agent_name'], 'agent_medium', false, true, false, '[&hellip;]', 'font-size:7.5pt;');
			$data[1] .= '</a></strong>';
		}
		
		
		$data[2] = html_print_image('images/' . modules_show_icon_type ($row['module_type']), true);
		if (check_acl ($config['id_user'], $row['id_group'], 'AW')) {
			$show_edit_icon = true;
			if (defined('METACONSOLE')) {
				if (!can_user_access_node ()) {
					$show_edit_icon = false;
				}
				
				$url_edit_module = $row['server_url'] . 'index.php?' .
					'sec=gagente&amp;' .
					'sec2=godmode/agentes/configurar_agente&amp;' .
					'id_agente=' . $row['id_agent'] . '&amp;' .
					'tab=module&amp;' .
					'id_agent_module=' . $row['id_agente_modulo'] . '&amp;' .
					'edit_module=1' .
					'&amp;loginhash=auto&amp;loginhash_data=' . $row['hashdata'] . '&amp;loginhash_user=' . str_rot13($row['user']);
			}
			else {
				$url_edit_module = 'index.php?' .
					'sec=gagente&amp;' .
					'sec2=godmode/agentes/configurar_agente&amp;' .
					'id_agente=' . $row['id_agent'] . '&amp;' .
					'tab=module&amp;' .
					'id_agent_module=' . $row['id_agente_modulo'] . '&amp;' .
					'edit_module=1';
			}
			
			if ($show_edit_icon) {
				$data[2] .= '<a href="' . $url_edit_module . '">' .
					html_print_image('images/config.png', true,
						array('alt' => '0', 'border' => '', 'title' => __('Edit'))) .
					'</a>';
			}
		}
		
		$data[3] = ui_print_truncate_text($row['module_name'], 'agent_small', false, true, true);
		if ($row['extended_info'] != '') {
			$data[3] .= ui_print_help_tip ($row['extended_info'], true, '/images/default_list.png');
		}
		if ($row['tags'] != '') {
			$data[3] .= html_print_image('/images/tag_red.png', true,
				array(
					'title' => $row['tags'],
					'style' => 'width: 20px; margin-left: 3px;'));
		}
    $data[4] = servers_show_type ($row['id_modulo']);
    
		$data[5] = ($row['module_interval'] == 0) ?
			human_time_description_raw($row['agent_interval'])
			:
			human_time_description_raw($row['module_interval']);
		
		if ($row['utimestamp'] == 0 && (($row['module_type'] < 21 ||
			$row['module_type'] > 23) && $row['module_type'] != 100)) {
			$data[6] = ui_print_status_image(STATUS_MODULE_NO_DATA,
				__('NOT INIT'), true);
		}
		elseif ($row['estado'] == 0) {
			$data[6] = ui_print_status_image(STATUS_MODULE_OK,
				__('NORMAL') . ': ' . $row['datos'], true);
		}
		elseif ($row['estado'] == 1) {
			$data[6] = ui_print_status_image(STATUS_MODULE_CRITICAL,
				__('CRITICAL') . ': ' . $row['datos'], true);
		}
		elseif ($row['estado'] == 2) {
			$data[6] = ui_print_status_image(STATUS_MODULE_WARNING,
				__('WARNING') . ': ' . $row['datos'], true);
		}
		else {
			$last_status =  modules_get_agentmodule_last_status(
				$row['id_agente_modulo']);
			switch($last_status) {
				case 0:
					$data[6] = ui_print_status_image(STATUS_MODULE_UNKNOWN,
						__('UNKNOWN') . ' - ' . __('Last status') . " " .
						__('NORMAL') . ': ' . $row['datos'], true);
					break;
				case 1:
					$data[6] = ui_print_status_image(STATUS_MODULE_UNKNOWN,
						__('UNKNOWN') . ' - ' . __('Last status') ." " .
						__('CRITICAL') . ': ' . $row['datos'], true);
					break;
				case 2:
					$data[6] = ui_print_status_image(STATUS_MODULE_UNKNOWN,
						__('UNKNOWN') . ' - ' . __('Last status') . " " .
						__('WARNING') . ': ' . $row['datos'], true);
					break;
			}
		}
		
		$data[7] = "";
		
		$acl_graphs = false;
		
		// Avoid the check on the metaconsole. Too slow to show/hide an icon depending on the permissions
		if (!is_metaconsole()) {
			$acl_graphs = check_acl($config['id_user'], $row['id_group'], 'RR');
		}
		else {
			$acl_graphs = true;
		}
		
		if ($row['history_data'] == 1 && $acl_graphs) {
			$graph_type = return_graphtype ($row['module_type']);
			
			$url = ui_get_full_url('operation/agentes/stat_win.php', false, false, false);
			$handle = dechex(crc32($row['id_agente_modulo'].$row['module_name']));
			$win_handle = 'day_'.$handle;
			
			$graph_params = array(
					'type' => $graph_type,
					'period' => SECONDS_1DAY,
					'id' => $row['id_agente_modulo'],
					'label' => base64_encode($row['module_name']),
					'refresh' => SECONDS_10MINUTES
				);
			
			if (is_metaconsole() && isset($row['server_id'])) {
				$graph_params['avg_only'] = 1;
				// Set the server id
				$graph_params['server'] = $row['server_id'];
			}
			
			$graph_params_str = http_build_query($graph_params);
			
			$link = 'winopeng(\''.$url.'?'.$graph_params_str.'\',\''.$win_handle.'\')';
			
			$data[7] = '<a href="javascript:'.$link.'">' . html_print_image('images/chart_curve.png', true, array('border' => '0', 'alt' => '')) .  '</a>';
			
			$data[7] .= '<a href="javascript: ' .
				'show_module_detail_dialog(' .
					$row['id_agente_modulo'] . ', '.
					$row['id_agent'] . ', \'' .
					$row['server_name'] . '\', 0, ' . SECONDS_1DAY . ', \'' . $row['module_name'] . '\')">' .
				html_print_image ('images/binary.png', true,
					array ('border' => '0', 'alt' => '')) . '</a>';
					
			$data[7] .= '<span id=\'hidden_name_module_' . $row['id_agente_modulo'] . '\'
							style=\'display: none;\'>' .
							$row['module_name'] .
						'</span>';
		}
		
		$data[8] = ui_print_module_warn_value($row['max_warning'],
			$row['min_warning'], $row['str_warning'], $row['max_critical'],
			$row['min_critical'], $row['str_critical']);

		if (is_numeric($row['datos']) && !modules_is_string_type($row['module_type'])) {
			if ( $config['render_proc'] ) {
					switch($row['module_type']) {
						case 2:
						case 6:
						case 9:
						case 18:
						case 21:
						case 31:
							
							if ( $row['datos'] >= 1 ) 
								$salida = $config['render_proc_ok'];
							else
								$salida = $config['render_proc_fail'];
							break;
						default:	
							$salida = number_format($row['datos'], $config['graph_precision']);
							break;
					}
			}
			else {
				$salida = number_format($row['datos'], $config['graph_precision']);
			}
			
			// Show units ONLY in numeric data types
			if (isset($row['unit'])) {
				$salida .= '&nbsp;' . '<i>' . io_safe_output($row['unit']) . '</i>';
				$salida = ui_print_truncate_text($salida, 'agent_small', true, true, false, '[&hellip;]', 'font-size:7.5pt;');
			}
		}
		else {
			//Fixed the goliat sends the strings from web
			//without HTML entities
			if ($is_web_content_string) {
				$module_value = $row['datos'];
			}
			else {
				$module_value = io_safe_output($row['datos']);
			}
			
			$is_snapshot = is_snapshot_data ( $module_value );
			
			if (($config['command_snapshot']) && ($is_snapshot)) {
				$handle = 'snapshot_' . $row['id_agente_modulo'];
				$url = 'include/procesos.php?agente=' . $row['id_agente_modulo'];
				$win_handle = dechex(crc32($handle));
				
				$link = "winopeng_var('operation/agentes/snapshot_view.php?" .
					"id=" . $row['id_agente_modulo'] .
					"&refr=" . $row['current_interval'] .
					"&label=" . rawurlencode(urlencode(io_safe_output($row['module_name']))) . "','" . $win_handle . "', 700,480)";
				
				$salida = '<a href="javascript:' . $link . '">' .
					html_print_image('images/default_list.png', true,
						array('border' => '0',
							'alt' => '',
							'title' => __('Snapshot view'))) . '</a> &nbsp;&nbsp;';
			}
			else {
				
				$sub_string = substr(io_safe_output($row['datos']), 0, 12);
				if ($module_value == $sub_string) {
					if ($module_value == 0 && !$sub_string) {
						$salida = 0;
					}
					else {
						$salida = $row['datos'];
					}
				}
				else {
					//Fixed the goliat sends the strings from web
					//without HTML entities
					if ($is_web_content_string) {
						$sub_string = substr($row['datos'], 0, 12);
					}
					else {
						//Fixed the data from Selenium Plugin
						if ($module_value != strip_tags($module_value)) {
							$module_value = io_safe_input($module_value);
							$sub_string = substr($row['datos'], 0, 12);
						}
						else {
							$sub_string = substr(io_safe_output($row['datos']),0, 12);
						}
					}
					
					if ($module_value == $sub_string) {
						$salida = $module_value;
					}
					else {
						$salida = "<span " .
							"id='hidden_value_module_" . $row['id_agente_modulo'] . "'
							style='display: none;'>" .
							$module_value .
							"</span>" . 
							"<span " .
							"id='value_module_" . $row['id_agente_modulo'] . "'
							title='" . $module_value . "' " .
							"style='white-space: nowrap;'>" . 
							'<span id="value_module_text_' . $row['id_agente_modulo'] . '">' .
								$sub_string . '</span> ' .
							"<a href='javascript: toggle_full_value(" . $row['id_agente_modulo'] . ")'>" .
								html_print_image('images/rosette.png', true) . '</a></span>';
					}
				}
			}
		}
		
		$data[9] = $salida;
		
		if ($row['module_interval'] > 0)
			$interval = $row['module_interval'];
		else
			$interval = $row['agent_interval'];
		
		if ($row['estado'] == 3) {
			$option = array ('html_attr' => 'class="redb"','style' => 'font-size:7pt;');
		}
		else {
			$option = array ('style' => 'font-size:7pt;');
		}
		$data[10] = ui_print_timestamp ($row['utimestamp'], true, $option);
		
		array_push ($table->data, $data);
	}
	
	html_print_table ($table);
}
else
	ui_print_info_message ( array ( 'no_close' => true, 'message' => __('This group doesn\'t have any monitor') ) );
// End Build List Result
/////////////////////////////////////

echo "<div id='monitor_details_window'></div>";
//strict user hidden
echo '<div id="strict_hidden" style="display:none;">';
html_print_input_text('strict_user_hidden', $strict_user);
echo '</div>';

enterprise_hook('close_meta_frame');

ui_require_javascript_file('pandora_modules');

?>
<script type='text/javascript'>
	$(document).ready (function () {
		if ($('#ag_group').val() != 0) {
			$('#tag_filter').css('display', 'none');
			$('#tag_td').css('display', 'none');
		}
	
});


$('#moduletype').click(function(){
    jQuery.get ("ajax.php",
      {
    "page": "general/subselect_data_module",
    "module":$('#moduletype').val()},
      function (data, status){
        $("#datatypetittle").show ();
        $("#datatypebox").hide ()
          .empty ()
          .append (data)
          .show ();
      },
      "html"
    );

    return false;
  });
	
	$('#ag_group').change (function () {
		strict_user = $('#text-strict_user_hidden').val();
		
		if (($('#ag_group').val() != 0) && (strict_user != 0)) {
			$('#tag_filter').css('display', 'none');
			$('#tag_td').css('display', 'none');
		} else {
			$('#tag_filter').css('display', '');
			$('#tag_td').css('display', '');
		}
	});
	
	function toggle_full_value(id) {
		text = $('#hidden_value_module_' + id).html();
		old_text = $("#value_module_text_" + id).html();
		
		$("#hidden_value_module_" + id).html(old_text);
		
		$("#value_module_text_" + id).html(text);
	}
	
	// Show the modal window of an module
	function show_module_detail_dialog(module_id, id_agent, server_name, offset, period, module_name) {
		if (period == -1) {
			if ($("#period").length == 1) {
				period = $('#period').val();
			}
			else {
				period = <?php echo SECONDS_1DAY; ?>;
			}
		}
		title = <?php echo "\"" . __("Module: ") . "\"" ?>;
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
						title: title + module_name,
						overlay: {
							opacity: 0.5,
							background: "black"
						},
						width: 620,
						height: 500
					})
					.show ();
				
				refresh_pagination_callback (module_id, id_agent, server_name,module_name);
			}
		});
	}
	
	function refresh_pagination_callback (module_id, id_agent, server_name,module_name) {
		
		$(".binary_dialog").click( function() {
			var classes = $(this).attr('class');
			classes = classes.split(' ');
			var offset_class = classes[2];
			offset_class = offset_class.split('_');
			var offset = offset_class[1];
			
			var period = $('#period').val();
			
			show_module_detail_dialog(module_id, id_agent, server_name, offset, period,module_name);
			
			return false;
		});
	}
</script>
