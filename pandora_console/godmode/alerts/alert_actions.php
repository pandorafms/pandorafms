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
	pandora_audit("ACL Violation",
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

$update_action = (bool) get_parameter ('update_action');
$create_action = (bool) get_parameter ('create_action');
$delete_action = (bool) get_parameter ('delete_action');
$copy_action = (bool) get_parameter ('copy_action');

if ((!$copy_action) && (!$delete_action))
	// Header
	print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/god2.png", false, "", true);

if ($copy_action) {
	$id = get_parameter ('id');

	$al_action = get_alert_action ($id);

	if ($al_action !== false){
		// If user tries to copy an action with group=ALL
		if ($al_action['id_group'] == 0){
			// then must have "PM" access privileges
			if (! give_acl ($config['id_user'], 0, "PM")) {
				pandora_audit("ACL Violation",
					"Trying to access Alert Management");
				require ("general/noaccess.php");
				exit;
			}else
				// Header
				print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/god2.png", false, "", true);
		// If user tries to copy an action of others groups
		}else{
			$own_info = get_user_info ($config['id_user']);
			if ($own_info['is_admin'] || give_acl ($config['id_user'], 0, "PM"))
				$own_groups = array_keys(get_user_groups($config['id_user'], "LM"));
			else
				$own_groups = array_keys(get_user_groups($config['id_user'], "LM", false));
			$is_in_group = in_array($al_action['id_group'], $own_groups);
			// Then action group have to be in his own groups
			if ($is_in_group)
				// Header
				print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/god2.png", false, "", true);
			else{
				pandora_audit("ACL Violation",
				"Trying to access Alert Management");
				require ("general/noaccess.php");
				exit;
			}
		}		
	}else
		// Header
		print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/god2.png", false, "", true);

	
	$result = clone_alert_action ($id);
	
	if ($result) {
		pandora_audit("Command management", "Duplicate alert action " . $id . " clone to " . $result);
	}
	else {
		pandora_audit("Command management", "Fail try to duplicate alert action " . $id);
	}
	
	print_result_message ($result,
		__('Successfully copied'),
		__('Could not be copied'));
}

if ($create_action) {
	$name = (string) get_parameter ('name');
	$id_alert_command = (int) get_parameter ('id_command');
	$field1 = (string) get_parameter ('field1');
	$field2 = (string) get_parameter ('field2');
	$field3 = (string) get_parameter ('field3');
	$group = (string) get_parameter ('group');
	$action_threshold = (int) get_parameter ('action_threshold');

	$result = create_alert_action ($name, $id_alert_command,
		array ('field1' => $field1,
			'field2' => $field2,
			'field3' => $field3,
			'id_group' => $group,
			'action_threshold' => $action_threshold));
		
	$info = 'Name: ' . $name . ' ID alert Command: ' . $id_alert_command .
		' Field1: ' . $field1 . ' Field2: ' . $field2 . ' Field3: ' . $field3 . ' Group: ' . $group .
		' Action threshold: ' . $action_threshold;
		
	if ($result) {
		pandora_audit("Command management", "Create alert action " . $result, false, false, $info);
	}
	else {
		pandora_audit("Command management", "Fail try to create alert action", false, false, $info);
	}
	
	print_result_message ($result,
		__('Successfully created'),
		__('Could not be created'));
}

if ($update_action) {
	$id = (string) get_parameter ('id');

	$al_action = get_alert_action ($id);

	if ($al_action !== false){
		if ($al_action['id_group'] == 0){
			if (! give_acl ($config['id_user'], 0, "PM")) {
				pandora_audit("ACL Violation",
					"Trying to access Alert Management");
				require ("general/noaccess.php");
				exit;
			}else
				// Header
				print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/god2.png", false, "", true);
		}
	}else
		// Header
		print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/god2.png", false, "", true);


	$name = (string) get_parameter ('name');
	$id_alert_command = (int) get_parameter ('id_command');
	$field1 = (string) get_parameter ('field1');
	$field2 = (string) get_parameter ('field2');
	$field3 = (string) get_parameter ('field3');
	$group = get_parameter ('group');
	$action_threshold = (int) get_parameter ('action_threshold');

	$values = array ();
	$values['name'] = $name;
	$values['id_alert_command'] = $id_alert_command;
	$values['field1'] = $field1;
	$values['field2'] = $field2;
	$values['field3'] = $field3;
	$values['id_group'] = $group;
	$values['action_threshold'] = $action_threshold;

	$result = update_alert_action ($id, $values);
	
	$info = 'Name: ' . $name . ' ID alert Command: ' . $id_alert_command .
		' Field1: ' . $field1 . ' Field2: ' . $field2 . ' Field3: ' . $field3 . ' Group: ' . $group .
		' Action threshold: ' . $action_threshold;
		
	if ($result) {
		pandora_audit("Command management", "Update alert action " . $id, false, false, json_encode($values));
	}
	else {
		pandora_audit("Command management", "Fail try to update alert action " . $id, false, false, json_encode($values));
	}
	
	print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
}

if ($delete_action) {
	$id = get_parameter ('id');

	$al_action = get_alert_action ($id);

	if ($al_action !== false){
		// If user tries to delete an action with group=ALL
		if ($al_action['id_group'] == 0){
			// then must have "PM" access privileges
			if (! give_acl ($config['id_user'], 0, "PM")) {
				pandora_audit("ACL Violation",
					"Trying to access Alert Management");
				require ("general/noaccess.php");
				exit;
			}else
				// Header
				print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/god2.png", false, "", true);
		// If user tries to delete an action of others groups
		}else{
			$own_info = get_user_info ($config['id_user']);
			if ($own_info['is_admin'] || give_acl ($config['id_user'], 0, "PM"))
				$own_groups = array_keys(get_user_groups($config['id_user'], "LM"));
			else
				$own_groups = array_keys(get_user_groups($config['id_user'], "LM", false));
			$is_in_group = in_array($al_action['id_group'], $own_groups);
			// Then action group have to be in his own groups
			if ($is_in_group)
				// Header
				print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/god2.png", false, "", true);
			else{
				pandora_audit("ACL Violation",
				"Trying to access Alert Management");
				require ("general/noaccess.php");
				exit;
			}
		}	
	}else
		// Header
		print_page_header (__('Alerts').' &raquo; '.__('Alert actions'), "images/god2.png", false, "", true);


	$result = delete_alert_action ($id);
	
	if ($result) {
		pandora_audit("Command management", "Delete alert action " . $id);
	}
	else {
		pandora_audit("Command management", "Fail try to delete alert action " . $id);
	}
	
	print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

$table->width = '90%';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Group');
$table->head[2] = __('Copy');
$table->head[3] = __('Delete');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[2] = '40px';
$table->size[3] = '40px';
$table->align = array ();
$table->align[2] = 'center';
$table->align[3] = 'center';

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
	$data[1] = print_group_icon ($action["id_group"], true) .'&nbsp;'. printTruncateText(get_group_name ($action["id_group"], true));
	$data[2] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_actions&copy_action=1&id='.$action['id'].'"
		onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">' .
		print_image("images/copy.png", true) . '</a>';
	$data[3] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_actions&delete_action=1&id='.$action['id'].'"
		onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.
		print_image("images/cross.png", true) . '</a>';
	
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
