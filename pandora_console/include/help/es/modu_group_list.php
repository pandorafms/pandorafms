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

check_login();

if (! give_acl($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	return;
}

if (is_ajax ()) {
	$get_group_json = (bool) get_parameter ('get_group_json');
	$get_group_agents = (bool) get_parameter ('get_group_agents');
	
	if ($get_group_json) {
		$id_group = (int) get_parameter ('id_group');
		
		if (! give_acl ($config['id_user'], $id_group, "AR")) {
			audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
				"Trying to access Alert Management");
			echo json_encode (false);
			return;
		}
		
		$group = get_db_row ('tmodule_group', 'id_mg', $id_group);
		
		echo json_encode ($group);
		return;
	}
	
	return;
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

	$sql = sprintf ('INSERT INTO tmodule_group (name) 
			VALUES ("%s")',
			$name);
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

	$sql = sprintf ('UPDATE tmodule_group  SET name = "%s" WHERE id_mg = %d', $name,$id_group);
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
	
//	$sql = sprintf ('UPDATE tmodule_group set id_mg = 1 WHERE id_mg = %d', $id_group);
//	$result = mysql_query ($sql);
	$sql = sprintf ('DELETE FROM tmodule_group WHERE id_mg = %d', $id_group);
	$result = mysql_query ($sql);
	if (! $result)
		echo "<h3 class='error'>".__('There was a problem deleting group')."</h3>"; 
	else
		echo "<h3 class='suc'>".__('Group successfully deleted')."</h3>";
}

echo "<h2>".__('Module Group management')." &raquo; ";	
echo __('Module Groups defined in Pandora')."</h2>";

$table->width = '65%';
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Delete');
$table->align = array ();
$table->align[1] = 'center';
$table->data = array ();

//$groups = get_user_groups ($config['id_user']);

$sql = "SELECT * 
		FROM tmodule_group ";
$groups = get_db_all_rows_sql ($sql, true);


foreach ($groups as $id_group ) {
	$data = array ();
	
//	$group = get_db_row ('tmodule_group', 'id_mg', $id_group);
	
//	if (!empty ($group["icon"]))
//		$data[0] = '<img src="images/groups_small/'.$group["icon"].'.png" border="0">';
//	else
//		$data[0] = '&nbsp;';
	$data[0] = '<strong><a href="index.php?sec=gagente&sec2=godmode/groups/configure_modu_group&id_group='.$id_group["id_mg"].'">'.$id_group["name"].'</a></strong>';
//	$data[2] = get_group_name ($group["parent"]);
//	$data[3] = $group['disabled'] ? __('Disabled') : __('Enabled');
	$data[1] = '<a href="index.php?sec=gagente&sec2=godmode/groups/modu_group_list&id_group='.$id_group["id_mg"].'&delete_group=1" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;"><img border="0" src="images/cross.png"></a>';
	
	array_push ($table->data, $data);
}

print_table ($table);

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/groups/configure_modu_group">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Create module group'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';

?>
