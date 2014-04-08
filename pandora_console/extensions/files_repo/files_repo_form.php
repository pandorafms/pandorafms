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

$full_extensions_dir = $config['homedir'].DIRECTORY_SEPARATOR.EXTENSIONS_DIR.DIRECTORY_SEPARATOR;
require_once ($full_extensions_dir."files_repo".DIRECTORY_SEPARATOR."functions_files_repo.php");

$file = array();
$file['name'] = '';
$file['description'] = '';
$file['groups'] = array();
if (isset($file_id) && $file_id > 0) {
	$file = files_repo_get_files(array('id' => $file_id));
	if (empty($file)) {
		$file_id = 0;
	} else {
		$file = $file[$file_id];
	}
}

$table = new stdClass();
$table->width = '99.5%';
$table->style = array();
$table->style[0] = "font-weight: bold;";
$table->style[2] = "text-align: center;";
$table->colspan = array();
$table->data = array();

// GROUPS
$groups = groups_get_all();
// Add the All group to the beginning to be always the first
// Use this instead array_unshift to keep the array keys
$groups = array(0 => __('All')) + $groups;
$html = "";
$style = "style=\"vertical-align: middle; min-width: 60px;\"";
foreach ($groups as $id => $name) {
	$checked = in_array($id, $file['groups']);
	$checkbox = html_print_checkbox_extended ('groups[]', $id, $checked, false, '', 'class="chkb_group"', true);
	$html .= "<span $style>$name&nbsp;$checkbox</span>&nbsp;&nbsp;&nbsp;";
}
$row = array();
$row[0] = __('Groups');
$row[1] = $html;
$table->data[] = $row;
$table->colspan[][1] = 2;

// DESCRIPTION
$row = array();
$row[0] = __('Description');
$row[0] .= ui_print_help_tip(__('Only 200 characters are permitted'), true);
$row[1] = html_print_textarea('description', 3, 20, $file['description'], 'style="min-height: 40px; max-height: 40px; width: 98%;"', true);
$table->data[] = $row;
$table->colspan[][1] = 2;

// FILE and SUBMIT BUTTON
$row = array();
$row[0] = __('File');
if ($file_id > 0) {
	$row[1] = $file['name'];
	$row[2] = html_print_submit_button(__('Update'), 'submit', false, 'class="sub upd"', true);
	$row[2] .= html_print_input_hidden('update_file', 1, true);
	$row[2] .= html_print_input_hidden('file_id', $file_id, true);
} else {
	$row[1] = html_print_input_file('upfile', true);
	$row[2] = html_print_submit_button(__('Add'), 'submit', false, 'class="sub add"', true);
	$row[2] .= html_print_input_hidden('add_file', 1, true);
}
$table->data[] = $row;
$table->colspan[][1] = 1;

$url = ui_get_full_url("index.php?sec=gextensions&sec2=extensions/files_repo");
echo "<form method='post' action='$url' enctype='multipart/form-data'>";
html_print_table($table);
echo "</form>";

?>