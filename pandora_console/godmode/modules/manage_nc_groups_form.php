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

// Load global vars
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access SNMO Groups Management");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_network_components.php');

$id = (int) get_parameter ('id');

if ($id) {
	$group = get_network_component_group ($id);
	$name = $group['name'];
	$parent = $group['parent'];
} else {
	$name = '';
	$parent = '';
}

echo '<h2>'.__('Component group management').'</h2>';

$table->width = '50%';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->data = array ();

$table->data[0][0] = __('Name');
$table->data[0][1] = print_input_text ('name', $name, '', 15, 255, true);

$table->data[1][0] = __('Parent');
$table->data[1][1] = print_select (get_network_component_groups (),
	'parent', $parent, false, __('None'), 0, true, false, false);

echo '<form method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups">';
print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	print_input_hidden ('update', 1);
	print_input_hidden ('id', $id);
	print_submit_button (__('Update'), 'crt', false, 'class="sub upd"');
} else {
	print_input_hidden ('create', 1);
	print_submit_button (__('Create'), 'crt', false, 'class="sub next"');
}
echo '</div>';
echo '</form>';
?>
