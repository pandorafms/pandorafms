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

$searchModules = check_acl($config['id_user'], 0, "AR");

if (!$modules || !$searchModules) {
	echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {
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
	$table->head[8] = "";
	
	
	
	$table->align = array ();
	$table->align[0] = "left";
	$table->align[1] = "left";
	$table->align[2] = "center";
	$table->align[3] = "center";
	$table->align[4] = "center";
	$table->align[5] = "center";
	$table->align[6] = "right";
	$table->align[7] = "right";
	$table->align[8] = "center";
	
	$table->data = array ();
	
	$id_type_web_content_string = db_get_value('id_tipo', 'ttipo_modulo',
		'nombre', 'web_content_string');
	
	foreach ($modules as $module) {
		//Fixed the goliat sends the strings from web
		//without HTML entities
		if ($module['id_tipo_modulo'] == $id_type_web_content_string) {
			$module['datos'] = io_safe_input($module['datos']);
		}
		
		//Fixed the data from Selenium Plugin
		if ($module['datos'] != strip_tags($module['datos'])) {
			$module['datos'] = io_safe_input($module['datos']);
		}
		
		$agentCell = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=' . $module['id_agente'] . '">' .
			$module['agent_name'] . '</a>';
		
		$typeCell = ui_print_moduletype_icon($module["id_tipo_modulo"], true);
		
		$intervalCell = modules_get_interval ($module['id_agente_modulo']);
		
		if ($module['utimestamp'] == 0 &&
			(
				($module['id_tipo_modulo'] < 21 || $module['id_tipo_modulo'] > 23) &&
				$module['id_tipo_modulo'] != 100)
			) {
			$statusCell = ui_print_status_image(STATUS_MODULE_NO_DATA,
				__('NOT INIT'), true);
		}
		elseif ($module["estado"] == 0) {
			$statusCell = ui_print_status_image(STATUS_MODULE_OK,
				__('NORMAL') . ": " . $module["datos"], true);
		}
		elseif ($module["estado"] == 1) {
			$statusCell = ui_print_status_image(STATUS_MODULE_CRITICAL,
				__('CRITICAL') . ": " . $module["datos"], true);
		}
		elseif ($module["estado"] == 2) {
			$statusCell = ui_print_status_image(STATUS_MODULE_WARNING,
				__('WARNING') . ": " . $module["datos"], true);
		}
		else {
			$last_status = modules_get_agentmodule_last_status($module['id_agente_modulo']);
			switch($last_status) {
				case 0:
					$statusCell = ui_print_status_image(
						STATUS_MODULE_OK,
						__('UNKNOWN') . " - " . __('Last status') .
						" " . __('NORMAL') .": " . $module["datos"],
						true);
					break;
				case 1:
					$statusCell = ui_print_status_image(
						STATUS_MODULE_CRITICAL,
						__('UNKNOWN') . " - " . __('Last status') .
						" " . __('CRITICAL') . ": " . $module["datos"],
						true);
					break;
				case 2:
					$statusCell = ui_print_status_image(
						STATUS_MODULE_WARNING,
						__('UNKNOWN') . " - " . __('Last status') .
						" " . __('WARNING') . ": " . $module["datos"],
						true);
					break;
			}
		}
		
		$graphCell = "";
		if ($module['history_data'] == 1) {
			
			$graph_type = return_graphtype ($module["id_tipo_modulo"]);
			
			$name_module_type = modules_get_moduletype_name ($module["id_tipo_modulo"]);
			$handle = "stat" . $name_module_type . "_" . $module["id_agente_modulo"];
			$url = 'include/procesos.php?agente=' . $module["id_agente_modulo"];
			$win_handle = dechex(crc32($module["id_agente_modulo"] . $module["module_name"]));
			
			$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&period=86400&id=".$module["id_agente_modulo"]."&label=".base64_encode($module["module_name"])."&refresh=600','day_".$win_handle."')";
			
			$graphCell = '<a href="javascript:'.$link.'">' . html_print_image("images/chart_curve.png", true, array("border" => 0, "alt" => "")) . '</a>';
			$graphCell .= "&nbsp;<a href='index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=".$module["id_agente"]."&amp;tab=data_view&period=86400&amp;id=".$module["id_agente_modulo"]."'>" . html_print_image('images/binary.png', true, array("border" => "0", "alt" => "")) . "</a>";
		}
		
		if (is_numeric($module["datos"])) {
			$dataCell = format_numeric($module["datos"]);
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
			
			// There are carriage returns here ?
			// If carriage returns present... then is a "Snapshot" data (full command output)
			if (($config['command_snapshot']) && (preg_match ("/[\n]+/i", io_safe_output($module["datos"])))) {
				
				$handle = "snapshot"."_".$module["id_agente_modulo"];
				$url = 'include/procesos.php?agente='.$module["id_agente_modulo"];
				$win_handle=dechex(crc32($handle));
				
				$link ="winopeng_var('operation/agentes/snapshot_view.php?id=".$module["id_agente_modulo"]."&refr=".$module["current_interval"]."&label=".$module["nombre"]."','".$win_handle."', 700,480)"; 
				
				$dataCell = '<a href="javascript:'.$link.'">' . html_print_image("images/default_list.png", true, array("border" => '0', "alt" => "", "title" => __("Snapshot view"))) . '</a> &nbsp;&nbsp;';
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
					$dataCell = $module_value;
				}
				else {
					$dataCell = "<span " .
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
		
		if ($module['estado'] == 3) {
			$option = array ("html_attr" => 'class="redb"');
		}
		else {
			$option = array ();
		}
		$timestampCell = ui_print_timestamp ($module["utimestamp"], true, $option);
		
		
		$group_agent = agents_get_agent_group($module['id_agente']);
		
		if (check_acl ($config['id_user'], $group_agent, "AW")) {
			$edit_module = 'aaa';
			
			$url_edit = "index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente="
				. $module['id_agente'] . "&tab=module&id_agent_module=" . 
				$module["id_agente_modulo"] . "&edit_module=1";
			
			$edit_module = '<a href="' . $url_edit . '">' .
				html_print_image("images/config.png", true) . '</a>';
		}
		else {
			$edit_module = '';
		}
		
		
		array_push($table->data, array(
			$module['module_name'],
			$agentCell,
			$typeCell,
			$intervalCell,
			$statusCell,
			$graphCell,
			$dataCell,
			$timestampCell,
			$edit_module));
	}
	
	echo "<br />";
	ui_pagination ($totalModules);
	html_print_table ($table);
	unset($table);
	ui_pagination ($totalModules);
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
