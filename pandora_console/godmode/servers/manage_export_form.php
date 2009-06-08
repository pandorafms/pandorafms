<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Export Server Management");
	require ("general/noaccess.php");
	return;
}

$update = (int) get_parameter ("update");

if ($update) {
	$row = get_db_row ("tserver_export", "id", $update);
	$name = $row["name"];
	$export_server = $row["id_export_server"];
	$preffix = $row["preffix"];
	$interval = $row["interval"];
	$ip_server = $row["ip_server"];
	$connect_mode = $row["connect_mode"];
	$user = $row["user"];
	$password = $row["pass"];
	$port = $row["port"];
	$directory = $row["directory"];
	$options = $row["options"];
}
else {
	$name = '';
	$export_server = 0;
	$preffix = '';
	$interval = 300;
	$ip_server = '';
	$connect_mode = 'ssh';
	$user = 'pandora';
	$password = '';
	$port = 22;
	$directory = '/var/spool/pandora/data_in';
	$options = '';
}

echo '<h2>'.__('Pandora servers').' &raquo; '.__('export targets');
//print_help_icon ("exportserver");
echo '</h2>';

$table->width=700;
$table->cellspacing=4;
$table->class="databox_color";

echo '<form name="modulo" method="POST" action="index.php?sec=gservers&sec2=godmode/servers/manage_export&' . ($update ? "update=$update" : 'create=1') . '">';


// Name
$table->data[0][0] = __('Name');
$table->data[0][1] = print_input_text ('name', $name, '', 25, 0, true);

// Export server
$table->data[1][0] = __('Export server');
$table->data[1][1] = print_select_from_sql ('SELECT id_server, name FROM tserver WHERE export_server = 1 ORDER BY name',
			'export_server', $export_server, '', __('None'), 0, true);

// Preffix
$table->data[2][0] = __('Preffix');
$table->data[2][1] = print_input_text ('preffix', $preffix, '', 25, 0, true);

// Interval
$table->data[3][0] = __('Interval');
$table->data[3][1] = print_input_text ('interval', $interval, '', 25, 0, true);

// Address
$table->data[4][0] = __('Address');
$table->data[4][1] = print_input_text ('ip_server', $ip_server, '', 25, 0, true);

// Transfer mode
$table->data[5][0] = __('Transfer mode');
$transfer_mode_select = array (
		'tentacle' => 'tentacle',
		'ssh' => 'ssh',
		'ftp' => 'ftp',
		'local' => 'local');
$table->data[5][1] = print_select ($transfer_mode_select, "connect_mode", $connect_mode, '', '', '', true);

// User
$table->data[6][0] = __('User');
$table->data[6][1] = print_input_text ('user', $user, '', 25, 0, true);

// Password
$table->data[7][0] = __('Password');
$table->data[7][1] = print_input_password ('password', $password, '', 25, 0, true);

// Port
$table->data[8][0] = __('Port');
$table->data[8][1] = print_input_text ('port', $port, '', 25, 0, true);

// Directory
$table->data[9][0] = __('Target directory');
$table->data[9][1] = print_input_text ('directory', $directory, '', 25, 0, true);

// Options
$table->data[10][0] = __('Extra options');
$table->data[10][1] = print_input_text ('options', $options, '', 25, 0, true);

print_table ($table);

echo '<div class="action-buttons" style="width: 700px">';
if ($update) 
	echo print_submit_button (__('Update'),"crt",false,'class="sub upd"',true);
else
	echo print_submit_button (__('Add'),"crt",false,'class="sub wand"',true);
echo '</form>';
echo "</div>";


echo "</form>";

?>
