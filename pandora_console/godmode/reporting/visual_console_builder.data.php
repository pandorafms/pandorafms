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

if (! give_acl ($config['id_user'], 0, "IW")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_visual_map.php');

switch ($action) {
	case 'new':
		echo "<form method='post' action='index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "'>";
		print_input_hidden('action', 'save');
		break;
	case 'update':
	case 'save':
		echo "<form method='post' action='index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "&id_visual_console=" . $idVisualConsole . "'>";
		print_input_hidden('action', 'update');
		break;
	case 'edit':		
		echo "<form method='post' action='index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "&id_visual_console=" . $idVisualConsole . "'>";
		print_input_hidden('action', 'update');
		break;
}

$table->width = '70%';
$table->data = array ();
$table->data[0][0] = __('Name:');
$table->data[0][1] = print_input_text ('name', $visualConsoleName, '', 15, 50, true);
$table->data[1][0] = __('Group:');
$groups = get_user_groups ($config['id_user']);
$table->data[1][1] = print_select ($groups, 'id_group', $idGroup, '', '', '', true);
$table->data[2][0] = '';
$backgrounds_list = list_files ('images/console/background/', "jpg", 1, 0);
$backgrounds_list = array_merge ($backgrounds_list, list_files ('images/console/background/', "png", 1, 0));
$table->data[3][0] = __('Background');
$table->data[3][1] = print_select ($backgrounds_list, 'background', $background, '', 'None', '', true);
if ($action == 'new') {
	$textButtonSubmit = __('Save');
	$classButtonSubmit = 'sub wand';
}
else {
	$textButtonSubmit = __('Update');
	$classButtonSubmit = 'sub upd';
}
$table->rowstyle[4] = "text-align: right;";
$table->data[4][0] = '';
$table->data[4][1] = print_submit_button ($textButtonSubmit, 'update_layout', false, 'class="' . $classButtonSubmit . '"', true);

print_table($table);
echo "</form>";
?>