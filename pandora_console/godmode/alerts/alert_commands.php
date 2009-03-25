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
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

if (is_ajax ()) {
	$get_alert_command = (bool) get_parameter ('get_alert_command');
	if ($get_alert_command) {
		$id = (int) get_parameter ('id');
		$command = get_alert_command ($id);
		echo json_encode ($command);
	}
	return;
}

echo '<h1>'.__('Alert commands').'</h1>';
$update_command = (bool) get_parameter ('update_command');
$create_command = (bool) get_parameter ('create_command');
$delete_command = (bool) get_parameter ('delete_command');

if ($create_command) {
	$name = (string) get_parameter ('name');
	$command = (string) get_parameter ('command');
	$description = (string) get_parameter ('description');
	
	$result = create_alert_command ($name, $command,
		array ('description' => $description));
	
	print_error_message ($result, __('Successfully created'),
		__('Could not be created'));
}

if ($update_command) {
	$id = (int) get_parameter ('id');
	$alert = get_alert_command ($id);
	if ($alert['internal']) {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Alert Management");
		require ("general/noaccess.php");
		exit;
	}
	$name = (string) get_parameter ('name');
	$command = (string) get_parameter ('command');
	$description = (string) get_parameter ('description');
	
	$values = array ();
	$values['name'] = $name;
	$values['command'] = $command;
	$values['description'] = $description;
	$result = update_alert_command ($id, $values);
	
	print_error_message ($result, __('Successfully updated'),
		__('Could not be updated'));
}

if ($delete_command) {
	$id = (int) get_parameter ('id');
	
	// Internal commands cannot be deleted
	if (get_alert_command_internal ($id)) {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to access Alert Management");
		require ("general/noaccess.php");
		return;
	}
	
	$result = delete_alert_command ($id);
	
	print_error_message ($result, __('Successfully deleted'),
		__('Could not be deleted'));
}

$table->width = '90%';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Description');
$table->head[2] = __('Delete');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[2] = '40px';
$table->align = array ();
$table->align[2] = 'center';

$commands = get_db_all_rows_in_table ('talert_commands');
if ($commands === false)
	$commands = array ();

foreach ($commands as $command) {
	$data = array ();
	
	if (! $command['internal'])
		$data[0] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_command&id='.$command['id'].'">'.
			$command['name'].'</a>';
	else
		$data[0] = $command['name'];
	
	$data[1] = $command['description'];
	$data[2] = '';
	if (! $command['internal'])
		$data[2] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_commands&delete_command=1&id='.$command['id'].'"
			onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.
			'<img src="images/cross.png"></a>';
	
	array_push ($table->data, $data);
}

print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_command">';
print_submit_button (__('Create'), 'create', false, 'class="sub next"');
print_input_hidden ('create_alert', 1);
echo '</form>';
echo '</div>';
?>
