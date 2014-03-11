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
		if ($id_agente_modulo === false)
			return;
		$tags = tags_get_module_tags($id_agente_modulo);
		
		if ($tags === false)
			$tags = array();
		
		echo '<h3> Tag\'s information </h3>';
		foreach ($tags as $tag) {
			echo tags_get_name($tag).'<br>';
		}
		
		return;
	}
	return;
}

if (!isset ($id_agente)) {
	//This page is included, $id_agente should be passed to it.
	db_pandora_audit("HACK Attempt",
		"Trying to get to monitor list without id_agent passed");
	include ("general/noaccess.php");
	exit;
}

include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions_servers.php");
include_once($config['homedir'] . "/include/functions_tags.php");

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

// TODO: Clean extra_sql
$extra_sql = '';

// Get all module from agent
switch ($config["dbtype"]) {
	case "mysql":
	case "postgresql":
		$sql = sprintf("
			SELECT *
			FROM tagente_estado,
				(SELECT *
				FROM tagente_modulo
				WHERE id_agente = %d AND delete_pending = 0
					AND disabled = 0) tagente_modulo 
			LEFT JOIN tmodule_group
				ON tagente_modulo.id_module_group = tmodule_group.id_mg 
			WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND %s
				tagente_estado.utimestamp != 0
			ORDER BY tagente_modulo.id_module_group , %s  %s",
			$id_agente, $extra_sql, $order['field'], $order['order']);
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
				AND tagente_modulo.delete_pending = 0
				AND tagente_modulo.disabled = 0
				AND tagente_estado.utimestamp != 0 
			ORDER BY tagente_modulo.id_module_group , %s %s
			", $id_agente, $order['field'], $order['order']);
		break;
}


$modules = db_get_all_rows_sql ($sql);
if (empty ($modules)) {
	$modules = array ();
}
$table->width = "100%";
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
			$data[0] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;id_agente_modulo='.$module["id_agente_modulo"].'&amp;flag=1&amp;refr=60">' . html_print_image("images/target.png", true, array("border" => '0', "alt" => __('Force'))) . '</a>';
		}
		else {
			$data[0] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agente.'&amp;id_agente_modulo='.$module["id_agente_modulo"].'&amp;refr=60">' . html_print_image("images/refresh.png", true, array("border" => "0", "alt" => "refresh")) . '</a>';
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
		$data[2] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$id_agente.'&amp;tab=module&amp;id_agent_module='.$module["id_agente_modulo"].'&amp;edit_module='.$module["id_modulo"].'">' . html_print_image("images/config.png", true, array("alt" => '0', "border" => "")) . '</a>';
	
	
	
	
	$data[3] = ui_print_truncate_text($module["nombre"], 'module_medium');
	if (!empty($module["extended_info"])) {
		if ($module["extended_info"] != "") {
			$data[3] .= ui_print_help_tip ($module["extended_info"], true, '/images/comments.png');
		}
	}
	
	//Adds tag context information
	if (tags_get_modules_tag_count($module['id_agente_modulo']) > 0) {
		$data[3] .= ' <a class="tag_details" href="ajax.php?page=operation/agentes/estado_monitores&get_tag_tooltip=1&id_agente_modulo='.$module['id_agente_modulo'].'">' .
		html_print_image("images/tag_red.png", true, array("id" => 'tag-details-'.$module['id_agente_modulo'], "class" => "img_help")) . '</a> ';
	}
	
	
	
	
	$data[4] = ui_print_string_substr ($module["descripcion"], 60, true, 8);
	
	
	
	modules_get_status($module['id_agente_modulo'], $module['estado'],
		$module['datos'], $status, $title);
	
	$data[5] = ui_print_status_image($status, $title, true);
	
	
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
		if (is_numeric($module["datos"])) {
			$salida = format_numeric($module["datos"]);
			
			// Show units ONLY in numeric data types
			if (isset($module["unit"])){
				$salida .= "&nbsp;" . '<i>'. io_safe_output($module["unit"]) . '</i>';
			}
		}
		else {
			$module_value = io_safe_output($module["datos"]);
			
			// There are carriage returns here ?
			// If carriage returns present... then is a "Snapshot" data (full command output)
			if (($config['command_snapshot']) && (preg_match ("/[\n]+/i", io_safe_output($module["datos"])))) {

		                $handle = "snapshot"."_".$module["id_agente_modulo"];
                		$url = 'include/procesos.php?agente='.$module["id_agente_modulo"];
		                $win_handle=dechex(crc32($handle));

				$link ="winopeng_var('operation/agentes/snapshot_view.php?id=".$module["id_agente_modulo"]."&refr=".$module["current_interval"]."&label=".$module["nombre"]."','".$win_handle."', 700,480)"; 

                        	$salida = '<a href="javascript:'.$link.'">' . html_print_image("images/default_list.png", true, array("border" => '0', "alt" => "", "title" => __("Snapshot view"))) . '</a> &nbsp;&nbsp;';


                	//	$link ="winopeng('operation/agentes/snapshot_view.php?id=".$module["id_agente_modulo"]."',$win_handle)";
		//		$salida = '<a href="javascript:'.$link.'">' . html_print_image("images/default_list.png", true, array("border" => '0', "alt" => "")) . '</a> &nbsp;&nbsp;';
			
			}
			else {
				$sub_string = substr(io_safe_output($module["datos"]),0, 12);
				
				if ($module_value == $sub_string) {
					$salida = $module_value;
				}
				else {
					$salida = "<span " .
						"id='value_module_" . $module["id_agente_modulo"] . "'
						title='" . $module_value . "' " .
						"style='white-space: nowrap;'>" . 
						'<span id="value_module_text_' . $module["id_agente_modulo"] . '">' .
							$sub_string . '</span> ' .
						"<a href='javascript: toggle_full_value(" . $module["id_agente_modulo"] . ")'>" .
							html_print_image("images/rosette.png", true) . "" . "</span>";
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
		
		$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&amp;period=86400&amp;id=".$module["id_agente_modulo"]."&amp;label=".base64_encode($module["nombre"])."&amp;refresh=600','day_".$win_handle."')";
		
	//	if ($nombre_tipo_modulo != "log4x")
		$data[8] .= '<a href="javascript:'.$link.'">' . html_print_image("images/chart_curve.png", true, array("border" => '0', "alt" => "")) . '</a> &nbsp;&nbsp;';
		$data[8] .= "<a href='index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=$id_agente&tab=data_view&amp;period=86400&amp;id=".$module["id_agente_modulo"]."'>" . html_print_image('images/binary.png', true, array("border" => '0', "alt" => "")) . "</a>"; 
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
		value_title = $("#value_module_" + id).attr('title');
		
		$("#value_module_" + id).attr('title', $("#value_module_text_" + id).html());
		
		$("#value_module_text_" + id).html(value_title);
	}
</script>
<?php


if (empty ($table->data)) {
	echo '<div class="nf">' .
		__('This agent doesn\'t have any active monitors').'</div>';
}
else {
	echo "<h4 style='padding-top:0px !important;'>".__('Full list of monitors')."</h4>";
	html_print_table ($table);
}

unset ($table);
unset ($table_data);

ui_require_css_file ('cluetip');
ui_require_jquery_file ('cluetip');
?>

<script type="text/javascript">
/* <![CDATA[ */
	$("a.tag_details").cluetip ({
			arrows: true,
			attribute: 'href',
			cluetipClass: 'default'
		})
		.click (function () {
			return false;
		});
/* ]]> */
</script>
