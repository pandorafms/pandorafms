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

check_login ();

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access extensions list");
	include ("general/noaccess.php");
	exit;
}

if (sizeof ($config['extensions']) == 0) {
	echo '<h3>'.__('There are no extensions defined').'</h3>';
	return;
}

// Header
print_page_header (__('Extensions'). " &raquo;  ". __('Defined extensions'). " - ".$report["name"], "images/extensions.png", false, "", false, "" );

$delete = get_parameter ("delete", "");
$name = get_parameter ("name", "");

if ($delete != ""){
	if (!file_exists($config["homedir"]."/extensions/ext_backup"))
		mkdir($config["homedir"]."/extensions/ext_backup");
	$source = $config["homedir"]."/$delete.php";
	rename ($source, $config["homedir"]."/extensions/ext_backup/$name.php");
}

$table->width = '95%';
$table->head = array ();
$table->head[0] = __('Name');
if (give_acl ($config['id_user'], 0, "PM")){
	$table->head[1] = __('Delete');
}
$table->data = array ();

foreach ($config['extensions'] as $extension) {
	if ($extension['main_function'] == '')
		continue;
	if ($extension['operation_menu'] == null)
		continue;
		
	$data = array ();
	$data[0] = '<a href="index.php?sec=extensions&amp;sec2='.$extension['operation_menu']['sec2'].'" class="mn">'.$extension['operation_menu']['name'];

	if (give_acl ($config['id_user'], 0, "PM")) {
		$data[1] = '<a href="index.php?sec=extensions&amp;sec2=operation/extensions&delete='.$extension['operation_menu']['sec2'].'&name='.$extension['operation_menu']['name'].'" class="mn"><img src="images/cross.png"></a>';
	}

	array_push ($table->data, $data);
}

print_table ($table);
?>
