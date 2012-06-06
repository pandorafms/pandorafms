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

check_login ();

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access extensions list");
	include ("general/noaccess.php");
	exit;
}

// Header
ui_print_page_header (__('Extensions'). " &raquo; ". __('Defined extensions'), "images/extensions.png", false, "", false, "" );

if (sizeof ($config['extensions']) == 0) {
	echo '<h3>'.__('There are no extensions defined').'</h3>';
	return;
}

$delete = get_parameter ("delete", "");
$name = get_parameter ("name", "");

if ($delete != ""){
	if (!file_exists($config["homedir"]."/extensions/ext_backup"))
		mkdir($config["homedir"]."/extensions/ext_backup");
	$source = $config["homedir"]."/$delete.php";
	rename ($source, $config["homedir"]."/extensions/ext_backup/$name.php");
}


$table->width = '60%';
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Action');
$table->style = array();
$table->style[1] = 'text-align: center; font-weight: bolder;';
$table->data = array ();

foreach ($config['extensions'] as $extension) {
	if ($extension['main_function'] == '')
		continue;
	if ($extension['operation_menu'] == null)
		continue;

	// If metaconsole is activated skip extensions without fatherID in menu array (this sections and extensions are filtered in metaconsole mode)
	if (!empty($extension['operation_menu']['fatherId']) and !array_key_exists($extension['operation_menu']['fatherId'], $operation_menu_array))
		continue;
		
	$data = array ();
	$data[0] = $extension['operation_menu']['name'];
	$data[1] = '<a href="index.php?sec=extensions&amp;sec2='.$extension['operation_menu']['sec2'].'" class="mn">' . __('Execute') . '</a>';

	array_push ($table->data, $data);
}

html_print_table ($table);
?>
