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

require_once ("include/functions_alerts.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access Alert actions");
	require ("general/noaccess.php");
	exit;
}

if (is_ajax ()) {
	$get_alert_action = (bool) get_parameter ('get_alert_action');
	if ($get_alert_action) {
		$id = (int) get_parameter ('id');
		$action = get_alert_action ($id);
		$action['command'] = get_alert_action_alert_command ($action['id']);
		
		echo json_encode ($action);
	}
	return;
}

// Header
print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/god2.png", false, "", true);

$update_action = (bool) get_parameter ('update_action');
$create_action = (bool) get_parameter ('create_action');
$delete_action = (bool) get_parameter ('delete_action');

if ($create_action) {
	$name = (string) get_parameter ('name');
	$id_alert_command = (int) get_parameter ('id_command');
	$field1 = (string) get_parameter ('field1');
	$field2 = (string) get_parameter ('field2');
	$field3 = (string) get_parameter ('field3');
	$group = (string) get_parameter ('group');

	$result = create_alert_action ($name, $id_alert_command,
		array ('field1' => $field1,
			'field2' => $field2,
			'field3' => $field3,
			'id_group' => $group));
	
	print_result_message ($result,
		__('Successfully created'),
		__('Could not be created'));
}

if ($update_action) {
	$id = (string) get_parameter ('id');
	$name = (string) get_parameter ('name');
	$id_alert_command = (int) get_parameter ('id_command');
	$field1 = (string) get_parameter ('field1');
	$field2 = (string) get_parameter ('field2');
	$field3 = (string) get_parameter ('field3');
	$group = get_parameter ('group');

	$values = array ();
	$values['name'] = $name;
	$values['id_alert_command'] = $id_alert_command;
	$values['field1'] = $field1;
	$values['field2'] = $field2;
	$values['field3'] = $field3;
	$values['id_group'] = $group;

	$result = update_alert_action ($id, $values);
	
	print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
}

if ($delete_action) {
	$id = get_parameter ('id');
	
	$result = delete_alert_action ($id);
	
	print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

$table->width = '90%';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Group');
$table->head[2] = __('Delete');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[2] = '40px';
$table->align = array ();
$table->align[2] = 'center';

$actions = get_db_all_rows_in_table ('talert_actions');
if ($actions === false)
	$actions = array ();

$rowPair = true;
$iterator = 0;
foreach ($actions as $action) {
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;
	
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_action&id='.$action['id'].'">'.
		$action['name'].'</a>';
	$data[1] = print_group_icon ($action["id_group"], true) .'&nbsp;'. get_group_name ($action["id_group"], true);
	$data[2] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_actions&delete_action=1&id='.$action['id'].'"
		onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.
		'<img src="images/cross.png"></a>';
	
	array_push ($table->data, $data);
}
if (isset($data)){
	print_table ($table);
} else {
	echo "<div class='nf'>".__('No alert actions configured')."</div>";
}

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_action">';
print_submit_button (__('Create'), 'create', false, 'class="sub next"');
print_input_hidden ('create_alert', 1);
echo '</form>';
echo '</div>';
?>
