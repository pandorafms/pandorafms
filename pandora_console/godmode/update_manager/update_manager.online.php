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

ui_require_css_file('update_manager', 'godmode/update_manager/');
require_once("include/functions_update_manager.php");
enterprise_include_once("include/functions_update_manager.php");

$current_package = 0;
if (isset($config['current_package']))
	$current_package = $config['current_package'];

echo "<p><b>" . sprintf(__("The last version of package installed is: %d"),
	$current_package) . "</b></p>";

/* Translators: Do not translade Update Manager, it's the name of the program */
ui_print_info_message(
	'<p>' .
		__('The new <a href="http://updatemanager.sourceforge.net">Update Manager</a> client is shipped with Pandora FMS It helps system administrators to update their Pandora FMS automatically, since the Update Manager does the task of getting new modules, new plugins and new features (even full migrations tools for future versions) automatically.') .
	'</p>' .
	'<p>' .
		__('Update Manager is one of the most advanced features of Pandora FMS Enterprise version, for more information visit <a href="http://pandorafms.com">http://pandorafms.com</a>.') .
	'</p>' .
	'<p>' .
		__('Update Manager sends anonymous information about Pandora FMS usage (number of agents and modules running). To disable it, remove remote server address from Update Manager plugin setup.') .
	'</p>');

echo "<div id='box_online' style='text-align: center;'>";
	echo "<span class='loading' style=''>";
	echo "<img src='images/wait.gif' />";
	echo "</span>";
	
	echo "<div class='checking_package' style='width:100%; text-align: center; display: none;'>";
	echo __('Checking for the newest package.');
	echo "</div>";
	
	echo "<div class='downloading_package' style='width:100%; text-align: center; display: none;'>";
	echo __('Downloading for the newest package.');
	echo "</div>";
	
	echo "<div class='content'></div>";
	
	echo "<div class='progressbar' style='display: none;'><img class='progressbar_img' src='' /></div>";
	
echo "</div>";

$enterprise = enterprise_hook('update_manager_enterprise_main');
if ($enterprise == ENTERPRISE_NOT_HOOK) {
	//Open view
	update_manager_main();
}
?>