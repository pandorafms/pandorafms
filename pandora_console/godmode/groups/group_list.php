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
	$custom_id = (string) get_parameter ('custom_id');

	$sql = sprintf ('INSERT INTO tgrupo (nombre, icon, parent, disabled, custom_id) 
			VALUES ("%s", "%s", %d, %d, "%s")',
			$name, substr ($icon, 0, -4), $id_parent, $alerts_disabled, $custom_id);
	$result = mysql_query ($sql);
	if ($result) {
		echo "<h3 class='suc'>".__('Group successfully created')."</h3>"; 
	} else {
		echo "<h3 class='error'>".__('There was a problem creating group')."</h3>";	}
}

/* Update group */
if ($update_group) {
	$id_group = (int) get_parameter ('id_group');
	$name = (string) get_parameter ('name');
	$icon = (string) get_parameter ('icon');
	$id_parent = (int) get_parameter ('id_parent');
	$alerts_enabled = (bool) get_parameter ('alerts_enabled');
	$custom_id = (string) get_parameter ('custom_id');

	$sql = sprintf ('UPDATE tgrupo  SET nombre = "%s",
			icon = "%s", disabled = %d, parent = %d, custom_id = "%s"
			WHERE id_grupo = %d',
			$name, substr ($icon, 0, -4), !$alerts_enabled, $id_parent, $custom_id, $id_group);
	$result = process_sql ($sql);
	if ($result !== false) {
		echo "<h3 class='suc'>".__('Group successfully updated')."</h3>";
	} else {
		echo "<h3 class='error'>".__('There was a problem modifying group')."</h3>";
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
		echo "<h3 class='error'>".__('There was a problem deleting group')."</h3>"; 
	else
		echo "<h3 class='suc'>".__('Group successfully deleted')."</h3>";
}

echo "<h2>".__('Group management')." &gt; ";	
echo __('Groups defined in Pandora')."</h2>";

$table->width = '65%';
$table->head = array ();
$table->head[0] = __('Icon');
$table->head[1] = __('Name');
$table->head[2] = __('Parent');
$table->head[3] = __('Alerts');
$table->head[4] = __('Delete');
$table->align = array ();
$table->align[4] = 'center';
$table->data = array ();

$groups = get_user_groups ($config['id_user']);

foreach ($groups as $id_group => $group_name) {
	$data = array ();
	
	$group = get_db_row ('tgrupo', 'id_grupo', $id_group);
	
	$data[0] = '<img src="images/groups_small/'.$group["icon"].'.png" border="0">';
	$data[1] = '<strong><a href="index.php?sec=gagente&sec2=godmode/groups/configure_group&id_group='.$id_group.'">'.$group_name.'</a></strong>';
	$data[2] = get_group_name ($group["parent"]);
	$data[3] = $group['disabled'] ? __('Disabled') : __('Enabled');
	$data[4] = '<a href="index.php?sec=gagente&sec2=godmode/groups/group_list&id_group='.$id_group.'&delete_group=1" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;"><img border="0" src="images/cross.png"></a>';
	
	array_push ($table->data, $data);
}

print_table ($table);

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/groups/configure_group">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Create group'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';

?>
