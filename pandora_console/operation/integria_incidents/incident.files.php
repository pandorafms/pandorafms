<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $result;
$table->width = "98%";
$table->class = "databox";

$table->data = array();

$profiles = array();
$table->data[0][0] = "<b>".__('File')."</b><br/>".html_print_input_file ('new_file', true, array('size' => "50%"));

$table->data[1][0] = "<b>".__('Description')."</b><br/>".html_print_textarea('description', 3, 6, '' , '', true);

$form = "<form method='post' action='' enctype='multipart/form-data'>";
$form .= html_print_table($table, true);
$form .= html_print_submit_button(__('Add'), 'submit_button', false, '', true);
$form .= html_print_input_hidden('tab', 'files', true);
$form .= html_print_input_hidden('attach_file', '1', true);
$form .= html_print_input_hidden('id_incident', $id_incident, true);
$form .= "</form>";

ui_toggle($form, __('Add a file'));

unset($table);

$table->width = "98%";
$table->class = "databox";

$table->head[0] = __('Filename');
$table->head[1] = __('Timestamp');
$table->head[2] = __('Description');
$table->head[3] = __('Size');
$table->head[4] = __('Delete');

$table->data = array();

if(isset($result['file'][0]) && is_array($result['file'][0])){
	$files = $result['file'];
}
else {
	$files = $result;
}

$row = 0;
foreach($files as $value) {
	$table->data[$row][0] = '<a href="operation/integria_incidents/incident.download_file.php?tab=files&id_incident='.$value['id_incidencia'].'&id_file='.$value['id_attachment'].'&filename='.$value['filename'].'&id_user='.$config['id_user'].'&rintegria_server='.$config['rintegria_server'].'">'.$value['filename'].'</a>';
	$table->data[$row][1] = $value['id_usuario'];
	if(is_array($value['description'])) {
		$value['description'] = '';
	}
	$table->data[$row][2] = $value['description'];
	$table->data[$row][3] = $value['size'];
	$table->data[$row][4] = "<a href='index.php?sec=workspace&sec2=operation/integria_incidents/incident&tab=files&id_incident=".$value['id_incidencia']."&delete_file=".$value['id_attachment']."'>".html_print_image("images/cross.png", true, array('title' => __('Delete file')))."</a>";
	$row++;
}

html_print_table($table);

?>
