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

if (!isset ($id_agente)) {
	//This page is included, $id_agente should be passed to it.
	audit_db ($config['id_user'], $config['remote_addr'], "HACK Attempt",
			  "Trying to get to monitor list without id_agent passed");
	include ("general/noaccess.php");
	exit;
}

$id_agent = get_parameter('id_agente');
$url = 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $id_agent;
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

// Get all module from agent
$sql = sprintf ("
	SELECT *
	FROM tagente_estado, tagente_modulo
		LEFT JOIN tmodule_group
		ON tmodule_group.id_mg = tagente_modulo.id_module_group
	WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND tagente_modulo.id_agente = %d 
		AND tagente_modulo.disabled = 0
		AND tagente_modulo.delete_pending = 0
		AND tagente_estado.utimestamp != 0 
	ORDER BY tagente_modulo.id_module_group , %s %s
	", $id_agente, $order['field'], $order['order']);

$modules = get_db_all_rows_sql ($sql);
if (empty ($modules)) {
	$modules = array ();
}
$table->width = 750;
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";
$table->head = array ();
$table->data = array ();

$isFunctionPolicies = enterprise_include_once ('include/functions_policies.php');

$table->head[0] = "<span title='" . __('Force execution') . "'>".__('F.')."</span>";

if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
	$table->head[1] = "<span title='" . __('Policy') . "'>".__('P.')."</span>";
}

$table->head[2] = __('Type') . ' ' .
	'<a href="' . $url . '&sort_field=type&sort=up"><img src="images/sort_up.png" style="' . $selectTypeUp . '" /></a>' .
	'<a href="' . $url . '&sort_field=type&sort=down"><img src="images/sort_down.png" style="' . $selectTypeDown . '" /></a>';
$table->head[3] = __('Module name') . ' ' .
	'<a href="' . $url . '&sort_field=name&sort=up"><img src="images/sort_up.png" style="' . $selectNameUp . '" /></a>' .
	'<a href="' . $url . '&sort_field=name&sort=down"><img src="images/sort_down.png" style="' . $selectNameDown . '" /></a>';
$table->head[4] = __('Description');
$table->head[5] = __('Status') . ' ' .
	'<a href="' . $url . '&sort_field=status&sort=up"><img src="images/sort_up.png" style="' . $selectStatusUp . '" /></a>' .
	'<a href="' . $url . '&sort_field=status&sort=down"><img src="images/sort_down.png" style="' . $selectStatusDown . '" /></a>';
$table->head[6] = __('Data') . ' ' .
	'<a href="' . $url . '&sort_field=data&sort=up"><img src="images/sort_up.png" style="' . $selectDataUp . '" /></a>' .
	'<a href="' . $url . '&sort_field=data&sort=down"><img src="images/sort_down.png" style="' . $selectDataDown . '" /></a>';
$table->head[7] = __('Graph');
$table->head[8] = __('Last contact') . ' ' .
	'<a href="' . $url . '&sort_field=last_contact&sort=up"><img src="images/sort_up.png" style="' . $selectLastContactUp . '" /></a>' .
	'<a href="' . $url . '&sort_field=last_contact&sort=down"><img src="images/sort_down.png" style="' . $selectLastContactDown . '" /></a>';

$table->align = array("left","left","left","left","left","center");

$last_modulegroup = 0;
$rowIndex = 0;
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
	
	$data = array ();
	if (($module["id_modulo"] != 1) && ($module["id_tipo_modulo"] != 100)) {
		if ($module["flag"] == 0) {
			$data[0] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&id_agente_modulo='.$module["id_agente_modulo"].'&flag=1&refr=60"><img src="images/target.png" border="0" /></a>';
		}
		else {
			$data[0] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&id_agente_modulo='.$module["id_agente_modulo"].'&refr=60"><img src="images/refresh.png" border="0"></a>';
		}
	}
	else {
		$data[0] = '';
	}
	
	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		if ($module["id_policy_module"] != 0) {
			$linked = isModuleLinked($module['id_agente_modulo']);
			$id_policy = get_db_value_sql('SELECT id_policy FROM tpolicy_modules WHERE id = '.$module["id_policy_module"]);
			$name_policy = get_db_value_sql('SELECT name FROM tpolicies WHERE id = '.$id_policy);
			$policyInfo = infoModulePolicy($module["id_policy_module"]);
			
			$adopt = false;
			if (isModuleAdopt($module['id_agente_modulo'])) {
				$adopt = true;
			}
			
			if ($linked) {
				if ($adopt) {
					$img = 'images/policies_brick.png';
					$title = __('(Adopt) ') . $name_policy;
				}
				else {
					$img = 'images/policies.png';
					$title = $name_policy;
				}
			}
			else {
				if ($adopt) {
					$img = 'images/policies_not_brick.png';
					$title = __('(Unlinked) (Adopt) ') . $name_policy;
				}
				else {
					$img = 'images/unlinkpolicy.png';
					$title = __('(Unlinked) ') . $name_policy;
				}
			}

			$data[1] = '<a href="?sec=gpolicies&sec2=enterprise/godmode/policies/policies&id=' . $id_policy . '">' . 
				print_image($img,true, array('title' => $title)) .
				'</a>';
		}
		else {
			$data[1] = "";
		}	
	}

	$data[2] = show_server_type ($module['id_modulo']);

	if (give_acl ($config['id_user'], $id_grupo, "AW")) 
	  $data[2] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&id_agent_module='.$module["id_agente_modulo"].'&edit_module='.$module["id_modulo"].'"><img src="images/config.png"></a>';
	  
	$data[3] = print_string_substr ($module["nombre"], 25, true);
	$data[4] = print_string_substr ($module["descripcion"], 30, true);

	$status = STATUS_MODULE_WARNING;
	$title = "";

	if ($module["estado"] == 1) {
		$status = STATUS_MODULE_CRITICAL;
		$title = __('CRITICAL');
	}
	elseif ($module["estado"] == 2) {
		$status = STATUS_MODULE_WARNING;
		$title = __('WARNING');
	}
	elseif ($module["estado"] == 0) {
		$status = STATUS_MODULE_OK;
		$title = __('NORMAL');
	}
	elseif ($module["estado"] == 3) {
		$last_status =  get_agentmodule_last_status($module['id_agente_modulo']);
		switch($last_status) {
			case 0:
				$status = STATUS_MODULE_OK;
				$title = __('UNKNOWN')." - ".__('Last status')." ".__('NORMAL');
				break;
			case 1:
				$status = STATUS_MODULE_CRITICAL;
				$title = __('UNKNOWN')." - ".__('Last status')." ".__('CRITICAL');
				break;
			case 2:
				$status = STATUS_MODULE_WARNING;
				$title = __('UNKNOWN')." - ".__('Last status')." ".__('WARNING');
				break;
		}
	}
	
	if (is_numeric($module["datos"])) {
		$title .= ": " . format_for_graph($module["datos"]);
	}
	else {
		$title .= ": " . substr(safe_output($module["datos"]),0,42);
	}

	$data[5] = print_status_image($status, $title, true);

	if ($module["id_tipo_modulo"] == 24) { // log4x
		switch($module["datos"]) {
			case 10: $salida = "TRACE"; $style="font-weight:bold; color:darkgreen;"; break;
			case 20: $salida = "DEBUG"; $style="font-weight:bold; color:darkgreen;"; break;
			case 30: $salida = "INFO";  $style="font-weight:bold; color:darkgreen;"; break;
			case 40: $salida = "WARN";  $style="font-weight:bold; color:darkorange;"; break;
			case 50: $salida = "ERROR"; $style="font-weight:bold; color:red;"; break;
			case 60: $salida = "FATAL"; $style="font-weight:bold; color:red;"; break;
		}
		$salida = "<span style='$style'>$salida</span>";
	} else {
		if (is_numeric($module["datos"])){
			$salida = format_numeric($module["datos"]);
		}
		else {
			$salida = "<span title='".$module['datos']."' style='white-space: nowrap;'>".substr(safe_output($module["datos"]),0,12)."</span>";
		}
	}

	$data[6] = $salida;
	$graph_type = return_graphtype ($module["id_tipo_modulo"]);

	$data[7] = " ";
	if ($module['history_data'] == 1){
		$nombre_tipo_modulo = get_moduletype_name ($module["id_tipo_modulo"]);
		$handle = "stat".$nombre_tipo_modulo."_".$module["id_agente_modulo"];
		$url = 'include/procesos.php?agente='.$module["id_agente_modulo"];
		$win_handle=dechex(crc32($module["id_agente_modulo"].$module["nombre"]));

		$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&period=86400&id=".$module["id_agente_modulo"]."&label=".$module["nombre"]."&refresh=600','day_".$win_handle."')";

	//	if ($nombre_tipo_modulo != "log4x")
			$data[7] .= '<a href="javascript:'.$link.'"><img src="images/chart_curve.png" border=0></a>';
		$data[7] .= "&nbsp;<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=data_view&period=86400&id=".$module["id_agente_modulo"]."'><img border=0 src='images/binary.png'></a>";
	}
	
	if ($module['estado'] == 3) {
		$data[8] = '<span class="redb">';
	}
	else {
		$data[8] = '<span>';
	}
	$data[8] .= print_timestamp ($module["utimestamp"], true);
	$data[8] .= '</span>';
	
	array_push ($table->data, $data);
	$rowIndex++;
}

if (empty ($table->data)) {
	echo '<div class="nf">'.__('This agent doesn\'t have any active monitors').'</div>';
}
else {
	echo "<h3>".__('Full list of monitors')."</h3>";
	print_table ($table);
}

unset ($table);
unset ($table_data);
?>
