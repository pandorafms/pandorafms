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

check_login ();

if (! check_acl($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	return;
}

echo '<br><br><div style="width:90%;text-align:right;">';
echo '<form method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=editor">';
html_print_submit_button(__('Create response'), 'create_response_button', false, array('class' => 'sub next'));
echo '</form>';
echo '</div>';

$event_responses = db_get_all_rows_in_table('tevent_response');

if(empty($event_responses)) {
	ui_print_info_message(__('No responses found'));
	$event_responses = array();
	return;
}

$table->width = '90%';

$table->size = array();
$table->size[0] = '200px';
$table->size[2] = '70px';
$table->size[3] = '70px';

$table->style[2] = 'text-align:center;';
$table->style[2] = 'text-align:center;';

$table->head[0] = __('Name');
$table->head[1] = __('Description');
$table->head[2] = __('Group');
$table->head[3] = __('Actions');

$table->data = array();

foreach($event_responses as $response) {
	$data = array();
	$data[0] = '<a href="index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=editor&id_response='.$response['id'].'">'.$response['name'].'</a>';
	$data[1] = $response['description'];
	$data[2] = ui_print_group_icon ($response['id_group'], true);
	$data[3] = '<a href="index.php?sec=geventos&sec2=godmode/events/events&section=responses&action=delete_response&id_response='.$response['id'].'">'.html_print_image('images/cross.png', true, array('title'=>__('Delete'))).'</a>';
	$data[3] .= '&nbsp;<a href="index.php?sec=geventos&sec2=godmode/events/events&section=responses&mode=editor&id_response='.$response['id'].'">'.html_print_image('images/pencil.png', true, array('title'=>__('Edit'))).'</a>';
	$table->data[] = $data;
}

html_print_table($table);

?>
