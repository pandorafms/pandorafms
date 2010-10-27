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

check_login ();

if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	pandora_audit("ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}

$table = null;

$table->width = '80%';
$table->head[0] = '';
$table->head[1] = __('Name');
$table->head[2] = __('Description');
$table->head[3] = '';
$table->align[0] = 'center';
$table->align[3] = 'center';
$table->size[0] = '20px';
$table->size[3] = '20px';

$osList = get_db_all_rows_in_table('tconfig_os');

$table->data = array();
foreach ($osList as $os) {
	$data = array();
	$data[] = print_os_icon($os['id_os'], false, true);
	$data[] = '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&action=edit&tab=builder&id_os=' . $os['id_os'] . '">' . safe_output($os['name']) . '</a>';
	$data[] = printTruncateText(safe_output($os['description']), 25, true, true);
	if ($os['id_os'] > 13) {
		$data[] = '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&action=delete&tab=list&id_os=' . $os['id_os'] . '"><img src="images/cross.png" /></a>';
	}
	else {
		//The original icons of pandora don't delete.
		$data[] = '';
	}
	
	$table->data[] = $data;
}

print_table($table);
?>