<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP additions
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to Pandora FMS 1.2 graph code and new XML reporting template management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// Load global vars
require ("include/config.php");

check_login();

if (! give_acl($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	return;
}

if (defined ('AJAX')) {
	$get_group_json = (bool) get_parameter ('get_group_json');

	if ($get_group_json) {
		$id_group = (int) get_parameter ('id_group');

		$group = get_db_row ('tgrupo', 'id_grupo', $id_group);

		echo json_encode ($group);
		exit ();
	}

	exit ();
}

$create_group = (bool) get_parameter ('create_group');
$update_group = (bool) get_parameter ('update_group');
$delete_group = (bool) get_parameter ('delete_group');

/* Create group */
if ($create_group) {
	$name = (string) get_parameter ('name');
	$icon = (string) get_parameter ('icon');
	$id_parent = (int) get_parameter ('id_parent');
	$alerts_disabled = (bool) get_parameter ('alerts_disabled');

	$sql = sprintf ('INSERT INTO tgrupo (nombre, icon, parent, disabled) 
			VALUES ("%s", "%s", %d, %d)',
			$name, substr ($icon, 0, -4), $id_parent, $alerts_disabled);
	$result = mysql_query ($sql);
	if ($result) {
		echo "<h3 class='suc'>".__('create_group_ok')."</h3>"; 
	} else {
		echo "<h3 class='error'>".__('create_group_no')."</h3>";	}
}

/* Update group */
if ($update_group) {
	$id_group = (int) get_parameter ('id_group');
	$name = (string) get_parameter ('name');
	$icon = (string) get_parameter ('icon');
	$id_parent = (int) get_parameter ('id_parent');
	$alerts_enabled = (bool) get_parameter ('alerts_enabled');
	
	$sql = sprintf ('UPDATE tgrupo  SET nombre = "%s",
			icon = "%s", disabled = %d, parent = %d 
			WHERE id_grupo = %d',
			$name, substr ($icon, 0, -4), !$alerts_enabled, $id_parent, $id_group);
	$result = mysql_query ($sql);
	if ($result) {
		echo "<h3 class='suc'>".__('modify_group_ok')."</h3>";
	} else {
		echo "<h3 class='error'>".__('modify_group_no')."</h3>";
	}
}

/* Delete group */
if ($delete_group) {
	$id_group = (int) get_parameter ('id_group');
	
	$sql = sprintf ('UPDATE tagente set id_grupo = 1 WHERE id_grupo = %d', $id_group);
	$result = mysql_query ($sql);
	$sql = sprintf ('DELETE FROM tgrupo WHERE id_grupo = %d', $id_group);
	$result = mysql_query ($sql);
	if (! $result)
		echo "<h3 class='error'>".__('delete_group_no')."</h3>"; 
	else
		echo "<h3 class='suc'>".__('delete_group_ok')."</h3>";
}

echo "<h2>".__('group_management')." &gt; ";	
echo __('definedgroups')."</h2>";

$table->width = '65%';
$table->head = array ();
$table->head[0] = __('icon');
$table->head[1] = __('name');
$table->head[2] = __('parent');
$table->head[3] = __('alerts');
$table->head[4] = __('delete');
$table->align = array ();
$table->align[4] = 'center';
$table->data = array ();

$groups = get_user_groups ($config['id_user']);

foreach ($groups as $id_group => $group_name) {
	$data = array ();
	
	$group = get_db_row ('tgrupo', 'id_grupo', $id_group);
	
	$data[0] = '<img src="images/groups_small/'.$group["icon"].'.png" border="0">';
	$data[1] = '<strong><a href="index.php?sec=gagente&sec2=godmode/groups/configure_group&id_group='.$id_group.'">'.$group_name.'</a></strong>';
	$data[2] = dame_nombre_grupo ($group["parent"]);
	$data[3] = $group['disabled'] ? __('disabled') : __('enabled');
	$data[4] = '<a href="index.php?sec=gagente&sec2=godmode/groups/group_list&id_group='.$id_group.'&delete_group=1" onClick="if (!confirm(\' '.__('are_you_sure').'\')) return false;"><img border="0" src="images/cross.png"></a>';
	
	array_push ($table->data, $data);
}

print_table ($table);

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/groups/configure_group">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('create_group'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';

?>
