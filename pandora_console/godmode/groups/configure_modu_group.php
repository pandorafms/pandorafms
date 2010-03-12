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


global $config;

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Group Management2");
	require ("general/noaccess.php");
	return;
}

// Header
print_page_header (__("Module group management"), "images/god1.png", false, "", true, "");

// Init vars
$icon = "";
$name = "";
$id_parent = 0;
$alerts_disabled = 0;
$custom_id = "";

$create_group = (bool) get_parameter ('create_group');
$id_group = (int) get_parameter ('id_group');

if ($id_group) {
	$group = get_db_row ('tmodule_group', 'id_mg', $id_group);
	if ($group) {
		$name = $group["name"];
	} else {
		echo "<h3 class='error'>".__('There was a problem loading group')."</h3>";
		echo "</table>"; 
		include ("general/footer.php");
		exit;
	}
}


$table->width = '450px';
$table->data = array ();
$table->data[0][0] = __('Name');
$table->data[0][1] = print_input_text ('name', $name, '', 35, 100, true);


echo'</span>';
echo '<form name="grupo" method="post" action="index.php?sec=gagente&sec2=godmode/groups/modu_group_list">';
print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id_group) {
	print_input_hidden ('update_group', 1);
	print_input_hidden ('id_group', $id_group);
	print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
} else {
	print_input_hidden ('create_group', 1);
	print_submit_button (__('Create'), 'crtbutton', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';
?>

