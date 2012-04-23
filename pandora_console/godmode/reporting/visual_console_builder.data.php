<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Login check
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_visual_map.php');
require_once ('include/functions_users.php');

switch ($action) {
	case 'new':
		echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "'>";
		html_print_input_hidden('action', 'save');
		break;
	case 'update':
	case 'save':
		echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "&id_visual_console=" . $idVisualConsole . "'>";
		html_print_input_hidden('action', 'update');
		break;
	case 'edit':		
		echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "&id_visual_console=" . $idVisualConsole . "'>";
		html_print_input_hidden('action', 'update');
		break;
}

$table->width = '98%';
$table->data = array ();
$table->data[0][0] = __('Name:'). ui_print_help_tip (__("Use [ or ( as first character, for example '[*] Map name', to render this map name in main menu"), true);

$table->data[0][1] = html_print_input_text ('name', $visualConsoleName, '', 80, 100, true);
$table->data[1][0] = __('Group:');
$groups = users_get_groups ($config['id_user']);

$own_info = get_user_info($config['id_user']);
// Only display group "All" if user is administrator or has "PM" privileges
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$display_all_group = true;
else	
	$display_all_group = false;

$table->data[1][1] = html_print_select_groups($config['id_user'], "AR", $display_all_group, 'id_group', $idGroup, '', '', '', true);
$backgrounds_list = list_files ('images/console/background/', "jpg", 1, 0);
$backgrounds_list = array_merge ($backgrounds_list, list_files ('images/console/background/', "png", 1, 0));
$table->data[2][0] = __('Background');
$table->data[2][1] = html_print_select ($backgrounds_list, 'background', $background, '', '', 0, true);
if ($action == 'new') {
	$textButtonSubmit = __('Save');
	$classButtonSubmit = 'sub wand';
}
else {
	$textButtonSubmit = __('Update');
	$classButtonSubmit = 'sub upd';
}

html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button ($textButtonSubmit, 'update_layout', false, 'class="' . $classButtonSubmit . '"');
echo '</div>';

echo "</form>";
?>