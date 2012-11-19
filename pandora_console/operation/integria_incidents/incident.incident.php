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
global $result_resolutions;
global $result_status;
global $result_sources;
global $result_groups;
global $result_users;

require_once ("include/functions_events.php"); //To get events group information

$resolutions[0] = __('None');
if (isset ($result_resolutions['resolution'])) {
	foreach($result_resolutions['resolution'] as $res) {
		$resolutions[$res['id']] = $res['name'];
	}
}
if (isset ($result_status['status'])) {
	foreach($result_status['status'] as $st) {
		$status[$st['id']] = $st['name'];
	}
}
if (isset ($result_sources['source'])) {
	foreach($result_sources['source'] as $src) {
		$sources[$src['id']] = $src['name'];
	}
}
if (isset ($result_groups['group'])) {
	foreach($result_groups['group'] as $gr) {
		$groups[$gr['id']] = $gr['name'];
	}
}
if (isset ($result_users['id_user'])) {
	foreach($result_users['id_user'] as $usr) {
		$users[$usr] = $usr;
	}
}
if(!isset($result['id_incidencia'])) {
	$result['titulo'] = '';
	$result['sla_disabled'] = 0;
	$result['notify_email'] = 0;
	$result['estado'] = 0;
	$result['prioridad'] = 0;
	$result['resolution'] = 0;
	$result['id_parent'] = 0;
	$result['origen'] = 0;
	$result['id_incident_type'] = 0;
	$result['id_task'] = 0;
	$result['id_creator'] = $config['id_user'];
	$result['id_grupo'] = 0;
	$result['id_usuario'] = 0;
	$result['id_task'] = 0;
	$result['descripcion'] = '';
	$result['epilog'] = '';
	
	if (isset ($_GET["from_event"])) {
		$event = get_parameter ("from_event");
		$result['descripcion'] = io_safe_output(events_get_description ($event));
		$result['titulo'] = ui_print_truncate_text($result['descripcion'], 'description', false, true, false);
		unset ($event);
	}
}

$table->width = "98%";
$table->class = "databox";

$table->data = array();
$table->colspan[0][0] = 3;
$table->colspan[3][0] = 3;
$table->colspan[4][0] = 3;

$table->data[0][0] = "<b>".__('Title')."</b><br/>".html_print_input_text("title", $result['titulo'], '', 80, 255, true);
if (isset($result['id_incidencia'])) {
	$table->data[1][2] = "<b>".__('Assigned user')."</b><br/>".html_print_select ($users, 'id_user', $result['id_usuario'], '', '', 0, true, false, false);
}
else {
	$table->data[1][2] = "";
}
if (isset($groups)) {
	$table->data[1][0] = "<b>".__('Group')."</b><br/>".html_print_select ($groups, 'group', $result['id_grupo'], '', '', 0, true, false, false);
}
$table->data[1][1] = "<b>".__('Priority')."</b><br/>".html_print_select (incidents_get_priorities (), 'priority', $result['prioridad'], '', '', 0, true, false, false);
$table->data[1][2] = "<b>".__('Creator')."</b><br/>".$result['id_creator'];

if (isset($result['id_incidencia'])) {
	$table->data[2][0] = "<b>".__('Source')."</b><br/>".html_print_select ($sources, 'source', $result['origen'], '', '', 0, true, false, false);
	$table->data[2][1] = "<b>".__('Resolution')."</b><br/>".html_print_select ($resolutions, 'resolution', $result['resolution'], '', '', 0, true, false, false);
	$table->data[2][2] = "<b>".__('Status')."</b><br/>".html_print_select ($status, 'status', $result['estado'], '', '', 0, true, false, false);
}

if (is_array($result['descripcion'])) {
	$result['descripcion'] = "";
}

$table->data[3][0] = "<b>".__('Description')."</b><br/>".html_print_textarea("description", 10, 6, $result['descripcion'] , '', true);

if(isset($result['id_incidencia'])) {
if(is_array($result['epilog'])) {
	$result['epilog'] = implode(',', $result['epilog']);
}
$table->data[4][0] = "<b>".__('Resolution epilog')."</b><br/>".html_print_textarea("epilog", 10, 6, $result['epilog'] , '', true);
}

if(isset($result['id_incidencia'])) {
	echo "<form method='post' action=''>";
	html_print_table($table);
	html_print_submit_button(__('Update'), 'submit_button');
	html_print_input_hidden('tab', 'incident');
	html_print_input_hidden('update_incident', '1');
	html_print_input_hidden('id_incident', $result['id_incidencia']);
	echo "</form>";
}
else {
	echo "<form method='post' action=''>";
	html_print_table($table);
	html_print_submit_button(__('Create'), 'submit_button');
	html_print_input_hidden('tab', 'list');
	html_print_input_hidden('create_incident', '1');
	echo "</form>";
}
?>
