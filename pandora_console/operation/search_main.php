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

$searchModules = check_acl($config['id_user'], 0, "AR");
$searchAgents = check_acl($config['id_user'], 0, "AR");
$searchAlerts = check_acl($config['id_user'], 0, "AR");
$searchGraphs = check_acl($config["id_user"], 0, "RR");
$searchMaps = check_acl($config["id_user"], 0, "RR");
$searchReports = check_acl ($config["id_user"], 0, "RR");
$searchUsers = check_acl($config['id_user'], 0, "UM");

echo '<br><div style="margin:auto; width:90%; padding: 10px; background: #fff">';

$anyfound = false;

$table->id = 'summary';
$table->width = '98%';

$table->style = array ();
$table->style[0] = 'font-weight: bold; text-align: center;';
$table->style[1] = 'font-weight: bold; text-align: center;';
$table->style[2] = 'font-weight: bold; text-align: center;';
$table->style[3] = 'font-weight: bold; text-align: center;';
$table->style[4] = 'font-weight: bold; text-align: center;';
$table->style[5] = 'font-weight: bold; text-align: center;';
$table->style[6] = 'font-weight: bold; text-align: center;';
$table->style[7] = 'font-weight: bold; text-align: center;';
$table->style[8] = 'font-weight: bold; text-align: center;';
$table->style[9] = 'font-weight: bold; text-align: center;';
$table->style[10] = 'font-weight: bold; text-align: center;';
$table->style[11] = 'font-weight: bold; text-align: center;';

$table->data[0][0] = html_print_image ("images/agent.png", true, array ("title" => __('Agents found')));
$table->data[0][1] = "<a href='index.php?search_category=agents&keywords=".$keyword."&head_search_keywords=Search'>" .
	sprintf(__("%s Found"), $totalAgents) . "</a>";
$table->data[0][2] = html_print_image ("images/module.png", true, array ("title" => __('Modules found')));
$table->data[0][3] = "<a href='index.php?search_category=modules&keywords=".$keyword."&head_search_keywords=Search'>" .
	sprintf(__("%s Found"), $totalModules) . "</a>";
$table->data[0][4] = html_print_image ("images/bell.png", true, array ("title" => __('Alerts found')));
$table->data[0][5] = "<a href='index.php?search_category=alerts&keywords=".$keyword."&head_search_keywords=Search'>" .
	sprintf(__("%s Found"), $totalAlerts) . "</a>";
$table->data[0][6] = html_print_image ("images/input_user.png", true, array ("title" => __('Users found')));
$table->data[0][7] = "<a href='index.php?search_category=users&keywords=".$keyword."&head_search_keywords=Search'>" .
	sprintf(__("%s Found"), $totalUsers) . "</a>";
$table->data[0][8] = html_print_image ("images/chart_curve.png", true, array ("title" => __('Graphs found')));
$table->data[0][9] = "<a href='index.php?search_category=graphs&keywords=".$keyword."&head_search_keywords=Search'>" .
	sprintf(__("%s Found"), $totalGraphs) . "</a>";
$table->data[0][10] = html_print_image ("images/reporting.png", true, array ("title" => __('Reports found')));
$table->data[0][11] = "<a href='index.php?search_category=reports&keywords=".$keyword."&head_search_keywords=Search'>" .
	sprintf(__("%s Found"), $totalReports) . "</a>";
$table->data[0][12] = html_print_image ("images/visual_console_green.png", true, array ("title" => __('Maps found')));
$table->data[0][13] = "<a href='index.php?search_category=maps&keywords=".$keyword."&head_search_keywords=Search'>" .
	sprintf(__("%s Found"), $totalMaps) . "</a>";

html_print_table($table);

if ($searchAgents && $totalAgents > 0) {
	echo $list_agents;
	
	if ($count_agents_main < $totalAgents) {
		echo "<a href='index.php?search_category=modules&keywords=".$keyword."&head_search_keywords=Search'>" .
			sprintf(__('Show %s of %s. View all matches'),
				$count_agents_main, $totalAgents) .
			"</a>";
	}
	else {
		echo __('Show all agents.');
	}
}

echo '</div>';
?>
