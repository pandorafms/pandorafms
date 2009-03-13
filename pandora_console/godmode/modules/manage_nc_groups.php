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
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access SNMP Group Management");
	require ("general/noaccess.php");
	exit;
}

$create = (bool) get_parameter ('create');
$update = (bool) get_parameter ('update');
$delete = (bool) get_parameter ('delete');

echo '<h2>'.__('Module management').' &gt; '. __('Component group management').'</h2>';

if ($create) {
	$name = (string) get_parameter ('name');
	$parent = (int) get_parameter ('parent');
	
	$result = process_sql_insert ('tnetwork_component_group',
		array ('name' => $name,
			'parent' => $parent));
	print_error_message ($result,
		__('Created successfully'),
		__('Not created. Error inserting data'));
}

if ($update) {
	$id = (int) get_parameter ('id_sg');
	$name = (string) get_parameter ('name');
	$parent = (int) get_parameter ('parent');
	
	$result = process_sql_update ('tnetwork_component_group',
		array ('name' => $name,
			'parent' => $parent),
		array ('id_sg' => $id));
	print_error_message ($result,
		__('Updated successfully'),
		__('Not updated. Error updating data'));
}

if ($delete) { // if delete
	$id = (int) get_parameter ('id_sg');
	
	$result = process_sql_delete ('tnetwork_component_group',
		array ('id_sg' => $id));
	print_error_message ($result,
		__('Deleted successfully'),
		__('Not deleted. Error deleting data'));
}

$table->width = '90%';
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Parent');
$table->head[2] = __('Delete');
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->align = array ();
$table->align[2] = 'center';
$table->data = array ();

$groups = get_db_all_rows_filter ('tnetwork_component_group',
	array ('order' => 'parent'));
if ($groups === false)
	$groups = array ();

foreach ($groups as $group) {
	$data = array ();
	
	$data[0] = '<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups_form&edit=1&id_sg='.$group["id_sg"].'">'.$group["name"].'</a>';
	
	$data[1] = give_network_component_group_name ($group["parent"]);
	$data[2] = '<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups&delete=1&id_sg='.$group["id_sg"].'"
		onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;">
		<img src="images/cross.png"></a>';
	
	array_push ($table->data, $data);
}

print_table ($table);

echo '<form method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups_form">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_input_hidden ('create', 1);
print_submit_button (__('Create'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';
?>
