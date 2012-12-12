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

// Load global vars
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access SNMO Groups Management");
	require ("general/noaccess.php");
	exit;
}

require_once ($config['homedir'] . '/include/functions_network_components.php');

$id = (int) get_parameter ('id');

if (defined('METACONSOLE'))
	$sec = 'advanced';
else
	$sec = 'gmodules';

if ($id) {
	$group = network_components_get_group ($id);
	$name = $group['name'];
	$parent = $group['parent'];
}
else {
	$name = '';
	$parent = '';
}

$table->width = '98%';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->data = array ();

$table->data[0][0] = __('Name');
$table->data[0][1] = html_print_input_text ('name', $name, '', 15, 255, true);

$table->data[1][0] = __('Parent');
$table->data[1][1] = html_print_select (network_components_get_groups (),
	'parent', $parent, false, __('None'), 0, true, false, false);

echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/modules/manage_nc_groups">';
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	html_print_input_hidden ('update', 1);
	html_print_input_hidden ('id', $id);
	html_print_submit_button (__('Update'), 'crt', false, 'class="sub upd"');
}
else {
	html_print_input_hidden ('create', 1);
	html_print_submit_button (__('Create'), 'crt', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';
?>