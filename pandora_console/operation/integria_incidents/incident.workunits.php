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
global $id_incident;

$table->width = "98%";
$table->class = "databox";

$table->data = array();
$table->colspan[1][0] = 3;

$profiles = array();
$default_time = "0.25";
$table->data[0][0] = "<b>".__('Time used')."</b><br/>".html_print_input_text ('time_used', $default_time, '', 10, 255, true);
$table->data[0][1] = "<b>".__('Have cost')."</b><br/>".html_print_checkbox ('have_cost', '', false, true);
$table->data[0][2] = "<b>".__('Public')."</b><br/>".html_print_checkbox ('public', '', true, true);

$table->data[1][0] = "<b>".__('Description')."</b><br/>".html_print_textarea('description', 3, 6, '' , '', true);

$form = "<form method='post' action=''>";
$form .= html_print_table($table, true);
$form .= html_print_submit_button(__('Add'), 'submit_button', false, 'class="sub next"', true);
$form .= html_print_input_hidden('tab', 'workunits', true);
$form .= html_print_input_hidden('create_workunit', '1', true);
$form .= html_print_input_hidden('id_incident', $id_incident, true);
$form .= html_print_input_hidden('profile', '0', true);
$form .= "</form>";

ui_toggle($form, __('Add workunit'));

if(isset($result['workunit'][0]) && is_array($result['workunit'][0])){
	$workunits = $result['workunit'];
}
else {
	$workunits = $result;
}

foreach($workunits as $value) {
	$table->width = "98%";
	$table->class = "databox";
	$table->colspan[1][0] = 4;
	$table->size[0] = "80%";
	$table->size[1] = "20%";

	$table->data = array();

	$table->data[0][0] = $value['id_user']." ".__('said')." ".$value['timestamp'];
	$table->data[0][1] = $value['duration']." ".__('Hours')." ".__('Public').": ".$value['public'];
	
	$table->data[1][0] = $value['description'];

	html_print_table($table);
}
?>
