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

$id = (int) get_parameter ('id');

$name = '';
$command = '';
$description = '';
if ($id) {
	$alert = get_alert_command ($id);
	$name = $alert['name'];
	$command = $alert['command'];
	$description = $alert['description'];
}

echo '<h1>'.__('Configure alert command').'</h1>';

$table->width = '90%';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '20%';
$table->data = array ();
$table->data[0][0] = __('Name');
$table->data[0][1] = print_input_text ('name', $name, '', 35, 255, true);
$table->data[1][0] = __('Command');
$table->data[1][1] = print_input_text ('command', $command, '', 35, 255, true);
$table->data[2][0] = __('Description');
$table->data[2][1] = print_textarea ('description', 10, 30, $description, '', true);

echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/alert_commands">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	print_input_hidden ('id', $id);
	print_input_hidden ('update_command', 1);
	print_submit_button (__('Update'), 'create', false, 'class="sub upd"');
} else {
	print_input_hidden ('create_command', 1);
	print_submit_button (__('Create'), 'create', false, 'class="sub next"');
}
echo '</div>';
echo '</form>';
?>
