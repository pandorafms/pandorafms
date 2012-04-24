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


global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation", "Trying to access Group Management2");
	require ("general/noaccess.php");
	return;
}

$id_field = (int) get_parameter ('id_field', 0);
$name = (string) get_parameter ('name', '');
$display_on_front = (bool) get_parameter ('display_on_front', 0);

// Header
if ($id_field) {
	$field = db_get_row_filter('tagent_custom_fields',array('id_field' => $id_field));
	$name = $field['name'];
	$display_on_front = $field['display_on_front'];
	ui_print_page_header (__("Update agent custom field"), "images/note.png", false, "", true, "");
} else {
	ui_print_page_header (__("Create agent custom field"), "images/note.png", false, "", true, "");
}

$table->width = '98%';
$table->data = array ();
$table->data[0][0] = __('Name');
$table->data[0][1] = html_print_input_text ('name', $name, '', 35, 100, true);

$table->data[1][0] = __('Display on front').ui_print_help_tip (__('The fields with display on front enabled will be displayed into the agent details'), true);
$table->data[1][1] = html_print_checkbox ('display_on_front', 1, $display_on_front, true);

echo '<form name="field" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/fields_manager">';
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';

if ($id_field) {
	html_print_input_hidden ('update_field', 1);
	html_print_input_hidden ('id_field', $id_field);
	html_print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
} else {
	html_print_input_hidden ('create_field', 1);
	html_print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
}

echo '</div>';
echo '</form>';
?>
