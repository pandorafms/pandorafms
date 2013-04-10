<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
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
require_once ($config['homedir'].'/include/functions_users.php');

// TODO: CLEAN extra_sql
$extra_sql = '';

$searchAgents = check_acl($config['id_user'], 0, "AR");

if (!$agents || !$searchAgents) {
	echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class = "databox";
	
		$table->head = array ();
		$table->head[0] = __('Agent') . ' ' .
			'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=name&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp)) . '</a>' .
			'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=name&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown)) . '</a>';
		$table->head[1] = __('OS'). ' ' .
			'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=os&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectOsUp)) . '</a>' .
			'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=os&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectOsDown)) . '</a>';
		$table->head[2] = __('Interval'). ' ' .
			'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=interval&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectIntervalUp)) . '</a>' .
			'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=interval&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectIntervalDown)) . '</a>';
		$table->head[3] = __('Group'). ' ' .
			'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=group&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectGroupUp)) . '</a>' .
			'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=group&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectGroupDown)) . '</a>';
	$table->head[4] = __('Modules');
	$table->head[5] = __('Status');
	$table->head[6] = __('Alerts');
	$table->head[7] = __('Last contact'). ' ' .
			'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=last_contact&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectLastContactUp)) . '</a>' .
			'<a href="index.php?search_category=agents&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=last_contact&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectLastContactDown)) . '</a>';
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
		$agent_info = reporting_get_agent_module_info ($agent["id_agente"]);
		
		$counts_info = array('total_count' => $agent_info["modules"],
				'normal_count' => $agent_info["monitor_normal"],
				'critical_count' => $agent_info["monitor_critical"],
				'warning_count' => $agent_info["monitor_warning"],
				'unknown_count' => $agent_info["monitor_unknown"],
				'fired_count' => $agent_info["monitor_alertsfired"]);

		$modulesCell = reporting_tiny_stats($counts_info, true);
		
		if ($agent['disabled']) {
			$cellName = "<em>" . ui_print_agent_name ($agent["id_agente"], true, "text-transform: uppercase;") . ui_print_help_tip(__('Disabled'), true) . "</em>";
		}
		else {
			$cellName = ui_print_agent_name ($agent["id_agente"], true, "text-transform: uppercase;");
		}
		
		$last_time = strtotime ($agent["ultimo_contacto"]);
		$now = time ();
		$diferencia = $now - $last_time;
		$time = ui_print_timestamp ($last_time, true);
		$time_style = $time;
		if ($diferencia > ($agent["intervalo"] * 2))
			$time_style = '<b><span style="color: #ff0000">'.$time.'</span></b>';
		
		$manage_agent = '';
		
		if (check_acl ($config['id_user'], $agent['id_grupo'], "AW")) {
			$url_manage = 'index.php?sec=estado&sec2=godmode/agentes/configurar_agente&id_agente='. $agent["id_agente"];
			$manage_agent = '<a href="' . $url_manage . '">' .
				html_print_image("images/cog.png", true, array("title" => __('Manage'), "alt" => __('Manage'))) . '</a>';
		}
		
		array_push($table->data, array(
			$cellName,
			ui_print_os_icon ($agent["id_os"], false, true),
			$agent['intervalo'],
			ui_print_group_icon ($agent["id_grupo"], true),
			$modulesCell,
			$agent_info["status_img"],
			$agent_info["alert_img"],
			$time_style, $manage_agent));
	}
	
	echo "<br />";
	ui_pagination ($totalAgents);
	html_print_table ($table);
	unset($table);
	ui_pagination ($totalAgents);
}
?>
