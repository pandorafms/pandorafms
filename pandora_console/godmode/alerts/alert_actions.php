<?php 

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require_once ("include/config.php");
require_once ("include/functions_alerts.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Alert actions");
	require ("general/noaccess.php");
	exit;
}

if (defined ('AJAX')) {
	$get_alert_action = (bool) get_parameter ('get_alert_action');
	if ($get_alert_action) {
		$id = (int) get_parameter ('id');
		$action = get_alert_action ($id);
		$action['command'] = get_alert_action_alert_command ($action['id']);
		
		echo json_encode ($action);
	}
	return;
}

echo '<h1>'.__('Alert actions').'</h1>';

$update_action = (bool) get_parameter ('update_action');
$create_action = (bool) get_parameter ('create_action');
$delete_action = (bool) get_parameter ('delete_action');

if ($create_action) {
	$name = (string) get_parameter ('name');
	$id_alert_command = (int) get_parameter ('id_command');
	$field1 = (string) get_parameter ('field1');
	$field2 = (string) get_parameter ('field2');
	$field3 = (string) get_parameter ('field3');
	
	$result = create_alert_action ($name, $id_alert_command,
		array ('field1' => $field1,
			'field2' => $field2,
			'field3' => $field3));
	
	print_error_message ($result, __('Successfully created'),
		__('Could not be created'));
}

if ($update_action) {
	$id = (string) get_parameter ('id');
	$name = (string) get_parameter ('name');
	$id_alert_command = (int) get_parameter ('id_command');
	$field1 = (string) get_parameter ('field1');
	$field2 = (string) get_parameter ('field2');
	$field3 = (string) get_parameter ('field3');
	
	$result = update_alert_action ($id, $id_alert_command, $name,
		array ('field1' => $field1,
			'field2' => $field2,
			'field3' => $field3));
	
	print_error_message ($result, __('Successfully updated'),
		__('Could not be updated'));
}

if ($delete_action) {
	$id = get_parameter ('id');
	
	$result = delete_alert_action ($id);
	
	print_error_message ($result, __('Successfully deleted'),
		__('Could not be deleted'));
}

$table->width = '90%';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Delete');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[1] = '40px';
$table->align = array ();
$table->align[1] = 'center';

$actions = get_db_all_rows_in_table ('talert_actions');
if ($actions === false)
	$actions = array ();

foreach ($actions as $action) {
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_action&id='.$action['id'].'">'.
		$action['name'].'</a>';
	$data[1] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_actions&delete_action=1&id='.$action['id'].'"
		onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.
		'<img src="images/cross.png"></a>';
	
	array_push ($table->data, $data);
}

print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_action">';
print_submit_button (__('Create'), 'create', false, 'class="sub next"');
print_input_hidden ('create_alert', 1);
echo '</form>';
echo '</div>';
?>
