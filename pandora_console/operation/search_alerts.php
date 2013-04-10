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

include_once('include/functions_alerts.php');
enterprise_include_once('include/functions_policies.php');
include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . "/include/functions_modules.php");

$searchAlerts = check_acl($config['id_user'], 0, "AR");

if ($alerts === false || $totalAlerts == 0 || !$searchAlerts) {
	echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}
else {
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->width = "98%";
	$table->class = "databox";
	
	$table->head = array ();
	$table->head[0] = '' . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=disabled&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectDisabledUp)) . '</a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=disabled&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectDisabledDown)) . '</a>';
	$table->head[1] = __('Agent') . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=agent&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectAgentUp)) . '</a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=agent&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectAgentDown)) . '</a>';
	$table->head[2] = __('Module') . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=module&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectModuleUp)) . '</a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=module&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectModuleDown)) . '</a>';
	$table->head[3] = __('Template') . ' ' . 
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=template&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectTemplateUp)) . '</a>' .
		'<a href="index.php?search_category=alerts&keywords=' . $config['search_keywords'] . '&head_search_keywords=abc&offset=' . $offset . '&sort_field=template&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectTemplateDown)) . '</a>';
	$table->head[4] = __('Action');
	
	$table->align = array ();
	$table->align[0] = "center";
	$table->align[1] = "left";
	$table->align[2] = "left";
	$table->align[3] = "left";
	$table->align[4] = "left";
	
	$table->valign = array ();
	$table->valign[0] = "top";
	$table->valign[1] = "top";
	$table->valign[2] = "top";
	$table->valign[3] = "top";
	$table->valign[4] = "top";
	
	$table->data = array ();
	foreach ($alerts as $alert) {
		if ($alert['disabled'])
			$disabledCell = html_print_image ('images/lightbulb_off.png', true, array('title' => 'disable', 'alt' => 'disable'));
		else
			$disabledCell = html_print_image ('images/lightbulb.png', true, array('alt' => 'enable', 'title' => 'enable'));
		
		$actionCell = '';
		if (strlen($alert["actions"]) > 0) {
			$arrayActions = explode(',', $alert["actions"]);
			$actionCell = '<ul class="action_list">';
			foreach ($arrayActions as $action)
				$actionCell .= '<li><div><span class="action_name">' . $action . '</span></div><br /></li>';
			$actionCell .= '</ul>';
		}
		
		
		array_push($table->data, array(
		$disabledCell,
		//print_agent_name ($alert["id_agente"], true, "upper"),
		agents_get_name($alert["id_agente"]),
		$alert["module_name"],
		$alert["template_name"],$actionCell
		));
	}
	
	echo "<br />";ui_pagination ($totalAlerts);
	html_print_table ($table); unset($table);
	ui_pagination ($totalAlerts);
}
?>
