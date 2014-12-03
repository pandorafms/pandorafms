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

// Load globar vars
global $config;

//Ajax tooltip to deploy modules's tag info.
if (is_ajax ()) {
	$get_tag_tooltip = (bool) get_parameter ('get_tag_tooltip', 0);
	
	if ($get_tag_tooltip) {
		$id_agente_modulo = (int) get_parameter ('id_agente_modulo');
		if ($id_agente_modulo == false)
			return;
		$tags = tags_get_module_tags($id_agente_modulo);
		
		if ($tags === false)
			$tags = array();
		
		echo '<h3>' . __("Tag's information") . '</h3>';
		foreach ($tags as $tag) {
			echo tags_get_name($tag).'<br>';
		}
		
		return;
	}
	
	$get_relations_tooltip = (bool) get_parameter ('get_relations_tooltip', 0);
	
	if ($get_relations_tooltip) {
		$id_agente_modulo = (int) get_parameter ('id_agente_modulo');
		if ($id_agente_modulo == false)
			return;
		$id_agente = modules_get_agentmodule_agent($id_agente_modulo);
		
		$params = array(
			'id_agent' => $id_agente,
			'id_module' => $id_agente_modulo
		);
		$relations = modules_get_relations($params);
		
		if (empty($relations))
			return;
		
		$table_relations = new stdClass();
		$table_relations->id = 'module_' . $id_agente_modulo . '_relations';
		$table_relations->width = '98%';
		$table_relations->class = 'databox';
		$table_relations->style = array();
		$table_relations->style[0] = 'font-weight: bold;';
		$table_relations->style[2] = 'font-weight: bold;';
		$table_relations->head = array();
		$table_relations->head[0] = __("Relationship information");
		$table_relations->head_colspan[0] = 4;
		$table_relations->data = array();
		
		foreach ($relations as $relation) {
			if ($relation['module_a'] == $id_agente_modulo) {
				$id_module = $relation['module_b'];
			}
			else {
				$id_module = $relation['module_a'];
			}
			$id_agent = modules_get_agentmodule_agent($id_module);
			
			$data = array();
			$data[0] = __('Agent');
			$data[1] = ui_print_agent_name ($id_agent, true);
			$data[2] = __('Module');
			$data[3] = "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente
				&id_agente=$id_agent&tab=module&edit_module=1&id_agent_module=$id_module'>" .
				ui_print_truncate_text(modules_get_agentmodule_name($id_module), 'module_medium', true, true, true, '[&hellip;]') . "</a>";
			$table_relations->data[] = $data;
		}
		html_print_table($table_relations);
		
		return;
	}
	return;
}

if (!isset ($id_agente)) {
	//This page is included, $id_agente should be passed to it.
	db_pandora_audit("HACK Attempt",
		"Trying to get the monitor list without id_agent passed");
	include ("general/noaccess.php");
	exit;
}

include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_servers.php");
include_once($config['homedir'] . "/include/functions_tags.php");
include_once($config['homedir'] . "/include/functions_clippy.php");

$id_agent = get_parameter('id_agente');
$url = 'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=' . $id_agent;
$selectTypeUp = '';
$selectTypeDown = '';
$selectNameUp = '';
$selectNameDown = '';
$selectStatusUp = '';
$selectStatusDown = '';
$selectDataUp = '';
$selectDataDown = '';
$selectLastContactUp = '';
$selectLastContactDown = '';
$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$selected = 'border: 1px solid black;';

switch ($sortField) {
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
	case 'name':
		switch ($sort) {
			case 'up':
				$selectNameUp = $selected;
				$order = array('field' => 'tagente_modulo.nombre', 'order' => 'ASC');
				break;
			case 'down':
				$selectNameDown = $selected;
				$order = array('field' => 'tagente_modulo.nombre', 'order' => 'DESC');
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
	case 'last_contact':
		switch ($sort) {
			case 'up':
				$selectLastContactUp = $selected;
				$order = array('field' => 'tagente_estado.utimestamp', 'order' => 'ASC');
				break;
			case 'down':
				$selectLastContactDown = $selected;
				$order = array('field' => 'tagente_estado.utimestamp', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectTypeUp = '';
		$selectTypeDown = '';
		$selectNameUp = $selected;
		$selectNameDown = '';
		$selectStatusUp = '';
		$selectStatusDown = '';
		$selectDataUp = '';
		$selectDataDown = '';
		$selectLastContactUp = '';
		$selectLastContactDown = '';
		
		$order = array('field' => 'tagente_modulo.nombre', 'order' => 'ASC');
		break;
}

switch ($config["dbtype"]) {
	case "oracle":
		if (isset($order['field']) && $order['field'] == 'tagente_modulo.nombre') {
			$order['field'] = 'dbms_lob.substr(tagente_modulo.nombre,4000,1)';
		}
		break;
}

// Fix: for tag functionality groups have to be all user_groups (propagate ACL funct!)
$groups = users_get_groups($config["id_user"]);

$tags_sql = tags_get_acl_tags($config['id_user'],
	array_keys($groups), 'AR', 'module_condition', 'AND', 'tagente_modulo');

$status_filter_monitor = (int)get_parameter('status_filter_monitor', -1);
$status_text_monitor = get_parameter('status_text_monitor', '');
$filter_monitors = (bool)get_parameter('filter_monitors', false);
$monitors_change_filter = (bool)get_parameter('monitors_change_filter', false);

$status_filter_sql = '1 = 1';
if ($status_filter_monitor == AGENT_MODULE_STATUS_NOT_NORMAL) { //Not normal
	$status_filter_sql = " tagente_estado.estado <> 0";
}
elseif ($status_filter_monitor != -1) {
	$status_filter_sql = 'tagente_estado.estado = ' . $status_filter_monitor;
}

$status_text_monitor_sql = '%';
if (!empty($status_text_monitor)) {
	$status_text_monitor_sql .= $status_text_monitor . '%';
}


//Count monitors/modules
switch ($config["dbtype"]) {
	case "mysql":
		$sql = sprintf("
			SELECT COUNT(*)
			FROM tagente_estado,
				(SELECT *
				FROM tagente_modulo
				WHERE id_agente = %d AND nombre LIKE \"%s\" AND delete_pending = 0
					AND disabled = 0) tagente_modulo 
			LEFT JOIN tmodule_group
				ON tagente_modulo.id_module_group = tmodule_group.id_mg 
			WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND %s %s 
				AND tagente_estado.estado != %d  
			ORDER BY tagente_modulo.id_module_group , %s  %s",
			$id_agente, $status_text_monitor_sql, $status_filter_sql, $tags_sql, AGENT_MODULE_STATUS_NO_DATA, $order['field'], $order['order']);
		break;
	case "postgresql":
		$sql = sprintf("
			SELECT COUNT(DISTINCT tagente_modulo.id_module_group)
			FROM tagente_estado,
				(SELECT *
				FROM tagente_modulo
				WHERE id_agente = %d AND nombre LIKE '%s'
					AND delete_pending = 0
					AND disabled = 0) tagente_modulo 
			LEFT JOIN tmodule_group
				ON tagente_modulo.id_module_group = tmodule_group.id_mg 
			WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND %s %s 
				AND tagente_estado.estado != %d
			GROUP BY tagente_modulo.id_module_group,
				tagente_modulo.nombre
			ORDER BY tagente_modulo.id_module_group , %s  %s",
			$id_agente, $status_text_monitor_sql, $status_filter_sql,
			$tags_sql, AGENT_MODULE_STATUS_NO_DATA, $order['field'],
			$order['order']);
		break;
	case "oracle":
		$sql = sprintf ("
			SELECT COUNT(*)" .
			" FROM tagente_estado, tagente_modulo
				LEFT JOIN tmodule_group
				ON tmodule_group.id_mg = tagente_modulo.id_module_group
			WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND tagente_modulo.id_agente = %d
				AND tagente_modulo.nombre LIKE '%s'
				AND %s %s
				AND tagente_modulo.delete_pending = 0
				AND tagente_modulo.disabled = 0
				AND tagente_estado.estado != %d 
			ORDER BY tagente_modulo.id_module_group , %s %s
			", $id_agente, $status_text_monitor_sql, $status_filter_sql, $tags_sql, AGENT_MODULE_STATUS_NO_DATA, $order['field'], $order['order']);
		break;
}

$count_modules = db_get_all_rows_sql($sql);
if (isset($count_modules[0]))
	$count_modules = reset($count_modules[0]);
else
	$count_modules = 0;


//Get monitors/modules
// Get all module from agent
switch ($config["dbtype"]) {
	case "mysql":
		$sql = sprintf("
			SELECT *
			FROM tagente_estado,
				(SELECT *
				FROM tagente_modulo
				WHERE id_agente = %d AND nombre LIKE \"%s\" AND delete_pending = 0
					AND disabled = 0) tagente_modulo 
			LEFT JOIN tmodule_group
				ON tagente_modulo.id_module_group = tmodule_group.id_mg 
			WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND %s %s 
				AND tagente_estado.estado != %d  
			ORDER BY tagente_modulo.id_module_group , %s  %s",
			$id_agente, $status_text_monitor_sql, $status_filter_sql, $tags_sql, AGENT_MODULE_STATUS_NO_DATA, $order['field'], $order['order']);
		break;
	case "postgresql":
		$sql = sprintf("
			SELECT *
			FROM tagente_estado,
				(SELECT *
				FROM tagente_modulo
				WHERE id_agente = %d AND nombre LIKE '%s' AND delete_pending = 0
					AND disabled = 0) tagente_modulo 
			LEFT JOIN tmodule_group
				ON tagente_modulo.id_module_group = tmodule_group.id_mg 
			WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND %s %s 
				AND tagente_estado.estado != %d  
			ORDER BY tagente_modulo.id_module_group , %s  %s",
			$id_agente, $status_text_monitor_sql, $status_filter_sql, $tags_sql, AGENT_MODULE_STATUS_NO_DATA, $order['field'], $order['order']);
		break;
	// If Dbms is Oracle then field_list in sql statement has to be recoded. See oracle_list_all_field_table()
	case "oracle":
		$fields_tagente_estado = oracle_list_all_field_table('tagente_estado', 'string');
		$fields_tagente_modulo = oracle_list_all_field_table('tagente_modulo', 'string');
		$fields_tmodule_group = oracle_list_all_field_table('tmodule_group', 'string');
		
		$sql = sprintf ("
			SELECT " . $fields_tagente_estado . ', ' . $fields_tagente_modulo . ', ' . $fields_tmodule_group .
			" FROM tagente_estado, tagente_modulo
				LEFT JOIN tmodule_group
				ON tmodule_group.id_mg = tagente_modulo.id_module_group
			WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND tagente_modulo.id_agente = %d
				AND tagente_modulo.nombre LIKE '%s'
				AND %s %s
				AND tagente_modulo.delete_pending = 0
				AND tagente_modulo.disabled = 0
				AND tagente_estado.estado != %d 
			ORDER BY tagente_modulo.id_module_group , %s %s
			", $id_agente, $status_text_monitor_sql, $tags_sql, $status_filter_sql, AGENT_MODULE_STATUS_NO_DATA, $order['field'], $order['order']);
		break;
}

if ($monitors_change_filter) {
	$limit = " LIMIT " . $config['block_size'] . " OFFSET 0";
}
else {
	$limit = " LIMIT " . $config['block_size'] . " OFFSET " . get_parameter ('offset',0);
}
$paginate_module = false;
if (isset($config['paginate_module']))
	$paginate_module = $config['paginate_module'];

if ($paginate_module) {
	$modules = db_get_all_rows_sql ($sql . $limit);
}
else {
	$modules = db_get_all_rows_sql ($sql);
}
if (empty ($modules)) {
	$modules = array ();
}
$table->width = "98%";
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";
$table->head = array ();
$table->data = array ();

$isFunctionPolicies = enterprise_include_once ('include/functions_policies.php');

$table->head[0] = "<span title='" . __('Force execution') . "'>" . __('F.') . "</span>";

if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
	$table->head[1] = "<span title='" . __('Policy') . "'>" . __('P.') . "</span>";
}

$table->head[2] = __('Type') . ' ' .
	'<a href="' . $url . '&sort_field=type&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectTypeUp, "alt" => "up")) . '</a>' .
	'<a href="' . $url . '&sort_field=type&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectTypeDown, "alt" => "down")) . '</a>';
$table->head[3] = __('Module name') . ' ' .
	'<a href="' . $url . '&sort_field=name&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up")) . '</a>' .
	'<a href="' . $url . '&sort_field=name&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';
$table->head[4] = __('Description');
$table->head[5] = __('Status') . ' ' .
	'<a href="' . $url . '&sort_field=status&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectStatusUp, "alt" => "up")) . '</a>' .
	'<a href="' . $url . '&sort_field=status&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectStatusDown, "alt" => "down")) . '</a>';
$table->head[6] = __('Warn'); 
$table->head[7] = __('Data') . ' ' .
	'<a href="' . $url . '&sort_field=data&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectDataUp, "alt" => "up")) . '</a>' .
	'<a href="' . $url . '&sort_field=data&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectDataDown, "alt" => "down")) . '</a>';
$table->head[8] = __('Graph');
$table->head[9] = __('Last contact') . ' ' .
	'<a href="' . $url . '&sort_field=last_contact&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectLastContactUp, "alt" => "up")) . '</a>' .
	'<a href="' . $url . '&sort_field=last_contact&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectLastContactDown, "alt" => "down")) . '</a>';

$table->align = array("left", "left", "center", "left", "left", "center");

$last_modulegroup = 0;
$rowIndex = 0;


$id_type_web_content_string = db_get_value('id_tipo', 'ttipo_modulo',
	'nombre', 'web_content_string');

$show_context_help_first_time = false;

foreach ($modules as $module) {
	//The code add the row of 1 cell with title of group for to be more organice the list.
	
	if ($module["id_module_group"] != $last_modulegroup)
	{
		$table->colspan[$rowIndex][0] = count($table->head);
		$table->rowclass[$rowIndex] = 'datos4';
		
		array_push ($table->data, array ('<b>'.$module['name'].'</b>'));
		
		$rowIndex++;
		$last_modulegroup = $module["id_module_group"];
	}
	//End of title of group
	
	//Fixed the goliat sends the strings from web
	//without HTML entities
	if ($module['id_tipo_modulo'] == $id_type_web_content_string) {
		$module['datos'] = io_safe_input($module['datos']);
	}
	
	
	$data = array ();
	if (($module["id_modulo"] != 1) && ($module["id_tipo_modulo"] != 100)) {
		if ($module["flag"] == 0) {
			$data[0] = '<a href="index.php?' .
				'sec=estado&amp;' .
				'sec2=operation/agentes/ver_agente&amp;' .
				'id_agente=' . $id_agente . '&amp;' .
				'id_agente_modulo=' . $module["id_agente_modulo"] . '&amp;' .
				'flag=1&amp;' .
				'refr=60">' . html_print_image("images/target.png", true, array("border" => '0', "title" => __('Force'))) . '</a>';
		}
		else {
			$data[0] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;id_agente_modulo='.$module["id_agente_modulo"].'&amp;refr=60">' . html_print_image("images/refresh.png", true, array("border" => "0", "title" => __("Refresh"))) . '</a>';
		}
	}
	else {
		$data[0] = '';
	}
	
	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		if ($module["id_policy_module"] != 0) {
			$linked = policies_is_module_linked($module['id_agente_modulo']);
			$id_policy = db_get_value_sql('SELECT id_policy FROM tpolicy_modules WHERE id = '.$module["id_policy_module"]);
			
			if ($id_policy != "")
				$name_policy = db_get_value_sql('SELECT name FROM tpolicies WHERE id = '.$id_policy);
			else
				$name_policy = __("Unknown");
			
			$policyInfo = policies_info_module_policy($module["id_policy_module"]);
			
			$adopt = false;
			if (policies_is_module_adopt($module['id_agente_modulo'])) {
				$adopt = true;
			}
			
			if ($linked) {
				if ($adopt) {
					$img = 'images/policies_brick.png';
					$title = '(' . __('Adopted') . ') ' . $name_policy;
				}
				else {
					$img = 'images/policies.png';
					$title = $name_policy;
				}
			}
			else {
				if ($adopt) {
					$img = 'images/policies_not_brick.png';
					$title = '(' . __('Unlinked') . ') (' . __('Adopted') . ') ' . $name_policy;
				}
				else {
					$img = 'images/unlinkpolicy.png';
					$title = '(' . __('Unlinked') . ') ' . $name_policy;
				}
			}
			
			$data[1] = '<a href="?sec=gpolicies&amp;sec2=enterprise/godmode/policies/policies&amp;id=' . $id_policy . '">' . 
				html_print_image($img,true, array('title' => $title)) .
				'</a>';
		}
		else {
			$data[1] = "";
		}
	}
	
	$data[2] = servers_show_type ($module['id_modulo']) . '&nbsp;';
	
	if (check_acl ($config['id_user'], $id_grupo, "AW")) 
		$data[2] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$id_agente.'&amp;tab=module&amp;id_agent_module='.$module["id_agente_modulo"].'&amp;edit_module='.$module["id_modulo"].'">' . html_print_image("images/config.png", true, array("alt" => '0', "border" => "", "title" => __('Edit'))) . '</a>';
	
	
	
	
	$data[3] = "";
	if ($module['quiet']) {
		$data[3] .= html_print_image("images/dot_green.disabled.png", true,
			array("border" => '0', "title" => __('Quiet'), "alt" => ""))
			. "&nbsp;";
	}
	$data[3] .= ui_print_truncate_text($module["nombre"], 'module_medium');
	if (!empty($module["extended_info"])) {
		if ($module["extended_info"] != "") {
			$data[3] .= ui_print_help_tip ($module["extended_info"], true, '/images/default_list.png');
		}
	}
	
	//Adds tag context information
	if (tags_get_modules_tag_count($module['id_agente_modulo']) > 0) {
		$data[3] .= ' <a class="tag_details" href="ajax.php?page=operation/agentes/estado_monitores&get_tag_tooltip=1&id_agente_modulo='.$module['id_agente_modulo'].'">' .
		html_print_image("images/tag_red.png", true, array("id" => 'tag-details-'.$module['id_agente_modulo'], "class" => "img_help")) . '</a> ';
	}
	
	//Adds relations context information
	if (modules_relation_exists($module['id_agente_modulo'])) {
		$data[3] .= ' <a class="relations_details" href="ajax.php?page=operation/agentes/estado_monitores&get_relations_tooltip=1&id_agente_modulo='.$module['id_agente_modulo'].'">' .
		html_print_image("images/link2.png", true, array("id" => 'relations-details-'.$module['id_agente_modulo'], "class" => "img_help")) . '</a> ';
	}
	
	
	$data[4] = ui_print_string_substr ($module["descripcion"], 60, true, 8);
	
	
	if ($module["datos"] != strip_tags($module["datos"])) {
		$module_value = io_safe_input($module["datos"]);
	}
	else {
		$module_value = io_safe_output($module["datos"]);
	}
	
	modules_get_status($module['id_agente_modulo'], $module['estado'],
		$module_value, $status, $title);
	
	
	$data[5] = ui_print_status_image($status, $title, true);
	if (!$show_context_help_first_time) {
		$show_context_help_first_time = true;
		
		if ($module['estado'] == AGENT_MODULE_STATUS_UNKNOWN) {
			$data[5] .= clippy_context_help("module_unknow");
		}
	}
	
	
	if ($module["id_tipo_modulo"] == 24) {
		// log4x
		switch($module["datos"]) {
			case 10:
				$salida = "TRACE";
				$style="font-weight:bold; color:darkgreen;";
				break;
			case 20:
				$salida = "DEBUG";
				$style="font-weight:bold; color:darkgreen;";
				break;
			case 30:
				$salida = "INFO";
				$style="font-weight:bold; color:darkgreen;";
				break;
			case 40:
				$salida = "WARN";
				$style="font-weight:bold; color:darkorange;";
				break;
			case 50:
				$salida = "ERROR";
				$style="font-weight:bold; color:red;";
				break;
			case 60:
				$salida = "FATAL";
				$style="font-weight:bold; color:red;";
				break;
		}
		$salida = "<span style='$style'>$salida</span>";
	}
	else {
		if (is_numeric($module["datos"]) && !modules_is_string_type($module['id_tipo_modulo'])){
			$salida = format_numeric($module["datos"]);
			
			// Show units ONLY in numeric data types
			if (isset($module["unit"])){
				$salida .= "&nbsp;" . '<i>'. io_safe_output($module["unit"]) . '</i>';
			}
		}
		else {
			//Fixed the goliat sends the strings from web
			//without HTML entities
			if ($module['id_tipo_modulo'] == $id_type_web_content_string) {
				$module_value = $module["datos"];
			}
			else {
				$module_value = io_safe_output($module["datos"]);
			}
			
			// If carriage returns present... then is a "Snapshot" data (full command output)
			$is_snapshot = is_snapshot_data ( $module_value );
			
			if (($config['command_snapshot']) && ($is_snapshot)) {
				$handle = "snapshot" . "_" . $module["id_agente_modulo"];
				$url = 'include/procesos.php?agente=' . $module["id_agente_modulo"];
				$win_handle = dechex(crc32($handle));
				
				$link ="winopeng_var('operation/agentes/snapshot_view.php?" .
					"id=" . $module["id_agente_modulo"] .
					"&refr=" . $module["current_interval"] .
					"&label=" . rawurlencode(urlencode(io_safe_output($module["nombre"]))) . "','".$win_handle."', 700,480)"; 
				
				$salida = '<a href="javascript:'.$link.'">' .
					html_print_image("images/default_list.png", true,
						array(
							"border" => '0',
							"alt" => "",
							"title" => __("Snapshot view"))) . '</a> &nbsp;&nbsp;';
			}
			else {
				//Fixed the goliat sends the strings from web
				//without HTML entities
				if ($module['id_tipo_modulo'] == $id_type_web_content_string) {
					$sub_string = substr($module_value, 0, 12);
				}
				else {
					//Fixed the data from Selenium Plugin
					if ($module_value != strip_tags($module_value)) {
						$module_value = io_safe_input($module_value);
						$sub_string = substr($module_value, 0, 12);
					}
					else {
						$sub_string = substr(io_safe_output($module_value),0, 12);
					}
				}
				
				
				if ($module_value == $sub_string) {
					$salida = $module_value;
				}
				else {
					$salida = "<span " .
						"id='hidden_value_module_" . $module["id_agente_modulo"] . "'
						style='display: none;'>" .
						$module_value .
						"</span>" . 
						"<span " .
						"id='value_module_" . $module["id_agente_modulo"] . "'
						title='" . $module_value . "' " .
						"style='white-space: nowrap;'>" . 
						'<span id="value_module_text_' . $module["id_agente_modulo"] . '">' .
							$sub_string . '</span> ' .
						"<a href='javascript: toggle_full_value(" . $module["id_agente_modulo"] . ")'>" .
							html_print_image("images/rosette.png", true) . "</a>" . "</span>";
				}
			}
		}
	}
	
	$data[6] = ui_print_module_warn_value ($module["max_warning"], $module["min_warning"], $module["str_warning"], $module["max_critical"], $module["min_critical"], $module["str_critical"]);
	
	$data[7] = $salida;
	$graph_type = return_graphtype ($module["id_tipo_modulo"]);
	
	$data[8] = " ";
	if ($module['history_data'] == 1) {
		$nombre_tipo_modulo = modules_get_moduletype_name ($module["id_tipo_modulo"]);
		$handle = "stat".$nombre_tipo_modulo."_".$module["id_agente_modulo"];
		$url = 'include/procesos.php?agente='.$module["id_agente_modulo"];
		$win_handle=dechex(crc32($module["id_agente_modulo"].$module["nombre"]));
		
		# Show events for boolean modules by default.
		if ($graph_type == 'boolean') {
			$draw_events = 1;
		} else {
			$draw_events = 0;
		}
		$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&amp;period=86400&amp;id=".$module["id_agente_modulo"]."&amp;label=".rawurlencode(urlencode(base64_encode($module["nombre"])))."&amp;refresh=600&amp;draw_events=$draw_events','day_".$win_handle."')";
		
		$data[8] .= '<a href="javascript:'.$link.'">' . html_print_image("images/chart_curve.png", true, array("border" => '0', "alt" => "")) . '</a> &nbsp;&nbsp;';
		$server_name = '';
		$data[8] .= "<a href='javascript: show_module_detail_dialog(" . $module["id_agente_modulo"] . ", ". $id_agente.", \"" . $server_name . "\", 0, 86400)'>". html_print_image ("images/binary.png", true, array ("border" => "0", "alt" => "")) . "</a>";
	}
	
	if ($module['estado'] == 3) {
		$data[9] = '<span class="redb">';
	}
	else {
		$data[9] = '<span>';
	}
	$data[9] .= ui_print_timestamp ($module["utimestamp"], true, array('style' => 'font-size: 7pt'));
	$data[9] .= '</span>';
	
	array_push ($table->data, $data);
	$rowIndex++;
}

?>
<script type="text/javascript">
	function toggle_full_value(id) {
		text = $("#hidden_value_module_" + id).html();
		old_text = $("#value_module_text_" + id).html();
		
		$("#hidden_value_module_" + id).html(old_text);
		
		$("#value_module_text_" + id).html(text);
	}
</script>
<?php

echo "<h4 style='padding-top:0px !important;'>";
ui_print_help_tip(__('For to view the list modules paginated, set in setup visuals.'));
echo __('Full list of monitors') . '&nbsp;' . reporting_tiny_stats ($agent, true, 'modules');
$modules_not_init = agents_monitor_notinit($id_agente);
if (!empty($modules_not_init)) {
	echo clippy_context_help("modules_not_init");
}
echo "</h4>";



print_form_filter_monitors($id_agente, $status_filter_monitor, $status_text_monitor);
if (empty ($table->data)) {
	if ($filter_monitors) {
		echo '<div class="nf">' .
			__('Any monitors aren\'t with this filter.') . '</div>';
	}
	else {
		echo '<div class="nf">' .
			__('This agent doesn\'t have any active monitors.') . '</div>';
	}
}
else {
	$url = "index.php?" .
		"sec=estado&" .
		"sec2=operation/agentes/ver_agente&" .
		"id_agente=" . $id_agente . "&" .
		"refr=&filter_monitors=1&" .
		"status_filter_monitor=" . $status_filter_monitor . "&" .
		"status_text_monitor=" . $status_text_monitor;
	
	if ($paginate_module) {
		ui_pagination ($count_modules, $url);
	}
	
	html_print_table ($table);
	
	if ($paginate_module) {
		ui_pagination ($count_modules, $url);
	}
}

unset ($table);
unset ($table_data);

ui_require_css_file ('cluetip');
ui_require_jquery_file ('cluetip');

echo "<div id='module_details_dialog'></div>";

ui_include_time_picker();
ui_require_jquery_file("ui.datepicker-" . get_user_language(), "include/javascript/i18n/");
?>

<script type="text/javascript">
/* <![CDATA[ */
	$("a.tag_details").cluetip ({
			arrows: true,
			clickThrough: false,
			attribute: 'href',
			cluetipClass: 'default'
		});
	$("a.relations_details").cluetip ({
			width: 500,
			arrows: true,
			clickThrough: false,
			attribute: 'href',
			cluetipClass: 'default',
			sticky: true,
			mouseOutClose: 'both',
			closeText: '<?php html_print_image("images/cancel.png") ?>'
		});
		
	// Show the modal window of an module
	function show_module_detail_dialog(module_id, id_agent, server_name, offset, period) {
		
		var server_name = '';
		var extra_parameters = '';
		if ($('input[name=selection_mode]:checked').val()) {
			
			period = $('#period').val();
			
			var selection_mode = $('input[name=selection_mode]:checked').val();
			var date_from = $('#text-date_from').val();
			var time_from = $('#text-time_from').val();
			var date_to = $('#text-date_to').val();
			var time_to = $('#text-time_to').val();
			
			extra_parameters = '&selection_mode=' + selection_mode + '&date_from=' + date_from + '&date_to=' + date_to + '&time_from=' + time_from + '&time_to=' + time_to;
		}
		
		$.ajax({
			type: "POST",
			url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
			data: "page=include/ajax/module&get_module_detail=1&server_name="+server_name+"&id_agent="+id_agent+"&id_module=" + module_id+"&offset="+offset+"&period="+period + extra_parameters,
			dataType: "html",
			success: function(data) {
				$("#module_details_dialog").hide ()
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
						width: 650,
						height: 500
					})
					.show ();
					refresh_pagination_callback (module_id, id_agent, "");
					datetime_picker_callback();
					forced_title_callback();
			}
		});
	}
	function datetime_picker_callback() {
		
		$("#text-time_from, #text-time_to").timepicker({
			showSecond: true,
			timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
			timeOnlyTitle: '<?php echo __('Choose time');?>',
			timeText: '<?php echo __('Time');?>',
			hourText: '<?php echo __('Hour');?>',
			minuteText: '<?php echo __('Minute');?>',
			secondText: '<?php echo __('Second');?>',
			currentText: '<?php echo __('Now');?>',
			closeText: '<?php echo __('Close');?>'});
			
		$("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
		
		$.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
	}
	datetime_picker_callback();
	
	function refresh_pagination_callback (module_id, id_agent, server_name) {
		$(".binary_dialog").click( function() {
			
			var classes = $(this).attr('class');
			classes = classes.split(' ');
			var offset_class = classes[2];
			offset_class = offset_class.split('_');
			var offset = offset_class[1];
			
			var period = $('#period').val();
			
			show_module_detail_dialog(module_id, id_agent, server_name, offset, period);
			return false;
		});
	}
/* ]]> */
</script>
<?php
function print_form_filter_monitors($id_agent, $status_filter_monitor = -1,
	$status_text_monitor = '') {
	
	$form_text = '<form action="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $id_agent . '" method="post">';
	
	$table->data[0][0] = html_print_input_hidden('filter_monitors', 1, true);
	$table->data[0][0] .= html_print_input_hidden('monitors_change_filter', 1, true);
	$table->data[0][0] .= __('Status:');
	$status_list = array(
		-1 => __('All'),
		AGENT_MODULE_STATUS_CRITICAL_BAD => __('Critical'),
		AGENT_MODULE_STATUS_CRITICAL_ALERT => __('Alert'),
		AGENT_MODULE_STATUS_NORMAL => __('Normal'),
		AGENT_MODULE_STATUS_NOT_NORMAL => __('Not Normal'),
		AGENT_MODULE_STATUS_WARNING => __('Warning'),
		AGENT_MODULE_STATUS_UNKNOWN => __('Unknown'));
	$table->data[0][1] = html_print_select ($status_list,
		'status_filter_monitor', $status_filter_monitor, '', '', 0,
		true);
	$table->data[0][2] = __('Free text for search (*):');
	$table->data[0][3] = html_print_input_text('status_text_monitor', $status_text_monitor, '', 30, 100, true);
	$table->data[0][4] = html_print_submit_button(__('Filter'), 'filter', false, 'class="sub search"', true);
	$table->data[0][4] .= '</form>';
	$table->data[0][5] = '<form action="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $id_agent . '" method="post">';
	$table->data[0][5] .= html_print_submit_button(__('Reset'), 'reset', false, 'class="sub upd"', true);
	$table->data[0][5] .= '</form>';
	html_print_submit_button(__('Filter'), 'filter', false, 'class="sub search"', true);
	$form_text .= html_print_table($table, true);
	
	$form_text .= '';
	
	$filter_hidden = false;
	
	if ($status_filter_monitor == -1 && $status_text_monitor == '') {
		$filter_hidden = true;
	}
	
	ui_toggle($form_text, __('Form filter'), __('Form filter'), $filter_hidden);
}
?>
