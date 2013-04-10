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

if ($searchAgents && $totalAgents > 0) {
	echo '<fieldset class="databox tactical_set">';
	echo '<legend>' . __('Agents') . '</legend>';
	echo sprintf(__("%d results found"), $totalAgents) . " <a href='index.php?search_category=agents&keywords=".$keyword."&head_search_keywords=Search'>" . html_print_image('images/zoom.png', true, array('title' => __('Show results'))) . "</a>";
	echo '</fieldset>';
	$anyfound = true;
}

if ($searchModules && $totalModules > 0) {
	echo '<fieldset class="databox tactical_set">';
	echo '<legend>' . __('Modules') . '</legend>';
	echo sprintf(__("%d results found"), $totalModules) . " <a href='index.php?search_category=modules&keywords=".$keyword."&head_search_keywords=Search'>" . html_print_image('images/zoom.png', true, array('title' => __('Show results'))) . "</a>";
	echo '</fieldset>';
	$anyfound = true;
}

if ($searchAlerts && $totalAlerts > 0) {
	echo '<fieldset class="databox tactical_set">';
	echo '<legend>' . __('Alerts') . '</legend>';
	echo sprintf(__("%d results found"), $totalAlerts) . " <a href='index.php?search_category=alerts&keywords=".$keyword."&head_search_keywords=Search'>" . html_print_image('images/zoom.png', true, array('title' => __('Show results'))) . "</a>";
	echo '</fieldset>';
	$anyfound = true;
}

if ($searchUsers && $totalUsers > 0) {
	echo '<fieldset class="databox tactical_set">';
	echo '<legend>' . __('Users') . '</legend>';
	echo sprintf(__("%d results found"), $totalUsers) . " <a href='index.php?search_category=users&keywords=".$keyword."&head_search_keywords=Search'>" . html_print_image('images/zoom.png', true, array('title' => __('Show results'))) . "</a>";
	echo '</fieldset>';
	$anyfound = true;
}

if ($searchGraphs && $totalGraphs > 0) {
	echo '<fieldset class="databox tactical_set">';
	echo '<legend>' . __('Graphs') . '</legend>';
	echo sprintf(__("%d results found"), $totalGraphs) . " <a href='index.php?search_category=graphs&keywords=".$keyword."&head_search_keywords=Search'>" . html_print_image('images/zoom.png', true, array('title' => __('Show results'))) . "</a>";
	echo '</fieldset>';
	$anyfound = true;
}

if ($searchReports && $totalReports > 0) {
	echo '<fieldset class="databox tactical_set">';
	echo '<legend>' . __('Reports') . '</legend>';
	echo sprintf(__("%d results found"), $totalReports) . " <a href='index.php?search_category=reports&keywords=".$keyword."&head_search_keywords=Search'>" . html_print_image('images/zoom.png', true, array('title' => __('Show results'))) . "</a>";
	echo '</fieldset>';
	$anyfound = true;
}

if ($searchMaps && $totalMaps > 0) {
	echo '<fieldset class="databox tactical_set">';
	echo '<legend>' . __('Maps') . '</legend>';
	echo sprintf(__("%d results found"), $totalMaps) . " <a href='index.php?search_category=maps&keywords=".$keyword."&head_search_keywords=Search'>" . html_print_image('images/zoom.png', true, array('title' => __('Show results'))) . "</a>";
	echo '</fieldset>';
	$anyfound = true;
}

if(!$anyfound) {
	echo "<br><div class='nf'>" . __("Zero results found") . "</div>\n";
}

echo '</div>';
?>
