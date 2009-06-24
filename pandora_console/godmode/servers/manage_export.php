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

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Export Server Management");
	require ("general/noaccess.php");
	exit;
}

$name = (string) get_parameter ("name");
$export_server = (int) get_parameter ("export_server");
$preffix = (string) get_parameter ("preffix");
$interval = (int) get_parameter ("interval");
$ip_server = (string) get_parameter ("ip_server");
$connect_mode = (string) get_parameter ("connect_mode");
$user = (string) get_parameter ("user");
$password = (string) get_parameter ("password");
$port = (string) get_parameter ("port");
$directory = (string) get_parameter ("directory");
$options = (string) get_parameter ("options");
$create = (int) get_parameter ("create");
$delete = (int) get_parameter ("delete");
$update = (int) get_parameter ("update");

// Update
if ($update) {
	$sql = sprintf ("UPDATE tserver_export SET name = '%s', id_export_server = %d,
	            preffix = '%s', `interval` = %d, ip_server = '%s', connect_mode = '%s',
				user = '%s', pass = '%s', port = %d, directory = '%s', options = '%s'
				WHERE id = %d",
				$name, $export_server, $preffix, $interval, $ip_server, $connect_mode, 
				$user, $password, $port, $directory, $options, $update);
	if (process_sql ($sql) === false) {
		echo '<h3 class="error">'.__('Error updating export target').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Successfully updated export target').'</h3>';
	}
}

// Delete
if ($delete) {
	$sql = sprintf("DELETE FROM tserver_export WHERE id = '%d'", $delete);
	if (process_sql ($sql) === false) {
		echo '<h3 class="error">'.__('Error deleting export target').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Successfully deleted export target').'</h3>';
	}
}

// Create
if ($create) {
	$sql = sprintf ("INSERT INTO tserver_export 
			(`name`, `id_export_server`, `preffix`, `interval`, `ip_server`, `connect_mode`,
			`user`, `pass`, `port`, `directory`, `options`) 
			VALUES ('%s', %d, '%s', %d, '%s', '%s', '%s', '%s', %d, '%s', '%s')",
			$name, $export_server, $preffix, $interval, $ip_server, $connect_mode,
			$user, $password, $port, $directory, $options);
	
	if (process_sql ($sql) === false) {
		echo '<h3 class="error">'.__('Error creating export target').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Successfully created export target').'</h3>';
	}
}

// List export servers
echo "<h2>".__('Pandora servers')." &raquo; ".__('export targets')."</h2>";

$result = get_db_all_rows_in_table ("tserver_export");
if (!$result) {
	echo '<div class="nf">'.__('There are no export targets configured').'</div>';
	echo '<div class="action-buttons" style="width: 700px">';
	echo '<form method="post" action="index.php?sec=gservers&sec2=godmode/servers/manage_export_form&create">';
	echo print_submit_button (__('Create'),"crt",false,'class="sub next"',true);
	echo '</form>';
	echo '</div>';
	return;
}

$table->head = array  (__('Name'), __('Preffix'), __('Interval'), __('Address'), __('Transfer mode'), __('Action'));
//$table->align = array ("","","","center","","","center","center");
$table->width = 700;
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";

foreach ($result as $row) {
	$table->data[] = array (
		// Name
		'<a href="index.php?sec=gservers&sec2=godmode/servers/manage_export_form&update=' . $row['id'] . '"><b>' . $row['name'] . '</b></a>',
		$row['preffix'],
		$row['interval'],
		$row['ip_server'],
		$row['connect_mode'],
		// Action
		'<a href="index.php?sec=gservers&sec2=godmode/servers/manage_export&delete=' . $row['id'] . '">
		<img src="images/cross.png" border="0" /></a>&nbsp;&nbsp;<a href="index.php?sec=gservers&sec2=godmode/servers/manage_export_form&update=' . $row['id'] . '">
		<img src="images/config.png" /></a>'
	);
}

print_table ($table);

echo '<div class="action-buttons" style="width: 700px">';
echo '<form method="post" action="index.php?sec=gservers&sec2=godmode/servers/manage_export_form&create">';
echo print_submit_button (__('Create'),"crt",false,'class="sub next"',true);
echo '</form>';
echo '</div>';

?>
