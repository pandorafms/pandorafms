<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

$searchModules = check_acl($config['id_user'], 0, 'AR');
$searchAgents = check_acl($config['id_user'], 0, 'AR');
$searchAlerts = check_acl($config['id_user'], 0, 'AR');
$searchGraphs = check_acl($config['id_user'], 0, 'RR');
$searchMaps = check_acl($config['id_user'], 0, 'RR');
$searchReports = check_acl($config['id_user'], 0, 'RR');
$searchUsers = check_acl($config['id_user'], 0, 'UM');
$searchPolicies = check_acl($config['id_user'], 0, 'AW');
$searchHelps = true;

echo '<br><div class="margin pdd_10px">';

$anyfound = false;

$table = new stdClass();
$table->id = 'summary';
$table->width = '98%';

$table->style = [];
$table->style[0] = 'font-weight: bold; text-align: left;';
$table->style[1] = 'font-weight: bold; text-align: left;';
$table->style[2] = 'font-weight: bold; text-align: left;';
$table->style[3] = 'font-weight: bold; text-align: left;';
$table->style[4] = 'font-weight: bold; text-align: left;';
$table->style[5] = 'font-weight: bold; text-align: left;';
$table->style[6] = 'font-weight: bold; text-align: left;';
$table->style[7] = 'font-weight: bold; text-align: left;';
$table->style[8] = 'font-weight: bold; text-align: left;';
$table->style[9] = 'font-weight: bold; text-align: left;';
$table->style[10] = 'font-weight: bold; text-align: left;';
$table->style[11] = 'font-weight: bold; text-align: left;';
$table->style[13] = 'font-weight: bold; text-align: left;';
$table->style[14] = 'font-weight: bold; text-align: left;';
$table->style[15] = 'font-weight: bold; text-align: left;';




$table->data[0][0] = html_print_image('images/agent.png', true, ['title' => __('Agents found'), 'class' => 'invert_filter']);
$table->data[0][1] = "<a href='index.php?search_category=agents&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".sprintf(__('%s Found'), $totalAgents).'</a>';
$table->data[0][2] = html_print_image('images/module.png', true, ['title' => __('Modules found'), 'class' => 'invert_filter']);
$table->data[0][3] = "<a href='index.php?search_category=modules&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".sprintf(__('%s Found'), $totalModules).'</a>';

// ------------------- DISABLED FOR SOME INSTALLATIONS------------------
// ~ $table->data[0][4] = html_print_image ("images/bell.png", true, array ("title" => __('Alerts found')));
// ~ $table->data[0][5] = "<a href='index.php?search_category=alerts&keywords=" . $config['search_keywords'] . "&head_search_keywords=Search'>" .
    // ~ sprintf(__("%s Found"), $totalAlerts) . "</a>";
// ---------------------------------------------------------------------
$table->data[0][6] = html_print_image('images/input_user.png', true, ['title' => __('Users found'), 'class' => 'invert_filter']);
$table->data[0][7] = "<a href='index.php?search_category=users&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".sprintf(__('%s Found'), $totalUsers).'</a>';
$table->data[0][8] = html_print_image('images/chart_curve.png', true, ['title' => __('Graphs found'), 'class' => 'invert_filter']);
$table->data[0][9] = "<a href='index.php?search_category=graphs&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".sprintf(__('%s Found'), $totalGraphs).'</a>';
$table->data[0][10] = html_print_image('images/reporting.png', true, ['title' => __('Reports found'), 'class' => 'invert_filter']);
$table->data[0][11] = "<a href='index.php?search_category=reports&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".sprintf(__('%s Found'), $totalReports).'</a>';
$table->data[0][12] = html_print_image('images/visual_console_green.png', true, ['title' => __('Visual consoles')]);
$table->data[0][13] = "<a href='index.php?search_category=maps&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".sprintf(__('%s Found'), $totalMaps).'</a>';
if (enterprise_installed()) {
    $table->data[0][14] = html_print_image('images/policies_mc.png', true, ['title' => __('Policies')]);
    $table->data[0][15] = "<a href='index.php?search_category=policies&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".sprintf(__('%s Found'), $totalPolicies).'</a>';
}

html_print_table($table);

if ($searchAgents && $totalAgents > 0) {
    echo $list_agents;

    echo "<a href='index.php?search_category=agents&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".sprintf(
        __('Show %s of %s. View all matches'),
        $count_agents_main,
        $totalAgents
    ).'</a>';
}


echo '</div>';
