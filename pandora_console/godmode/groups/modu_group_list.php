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

if (! check_acl($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	return;
}

if (is_ajax ()) {
	$get_group_json = (bool) get_parameter ('get_group_json');
	$get_group_agents = (bool) get_parameter ('get_group_agents');
	
	if ($get_group_json) {
		$id_group = (int) get_parameter ('id_group');
		
		if (! check_acl ($config['id_user'], $id_group, "AR")) {
			db_pandora_audit("ACL Violation",
				"Trying to access Alert Management");
			echo json_encode (false);
			return;
		}
		
		$group = db_get_row ('tmodule_group', 'id_mg', $id_group);
		
		echo json_encode ($group);
		return;
	}
	
	return;
}

// Header
ui_print_page_header (__("Module groups defined in Pandora"), "images/god1.png", false, "", true, "");

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
	$check = db_get_value('name', 'tmodule_group', 'name', $name);

	if ($name){
		if (!$check) {
			$result = db_process_sql_insert('tmodule_group', array('name' => $name));
	
			if ($result) {
				echo "<h3 class='suc'>".__('Group successfully created')."</h3>"; 
			}
			else {
				echo "<h3 class='error'>".__('There was a problem creating group')."</h3>";
			}
		}
		else {
			echo "<h3 class='error'>".__('Each module group must have a different name')."</h3>";
		}
	}
	else {
		echo "<h3 class='error'>".__('Module group must have a name')."</h3>";
	}
}

/* Update group */
if ($update_group) {
	$id_group = (int) get_parameter ('id_group');
	$name = (string) get_parameter ('name');
	$icon = (string) get_parameter ('icon');
	$id_parent = (int) get_parameter ('id_parent');
	$alerts_enabled = (bool) get_parameter ('alerts_enabled');
	$custom_id = (string) get_parameter ('custom_id');
	$check = db_get_value('name', 'tmodule_group', 'name', $name);
	$subcheck = db_get_value('name', 'tmodule_group', 'id_mg', $id_group);
	
	if ($name) {
		if (!$check || $subcheck == $name) {
			$result = db_process_sql_update('tmodule_group', array('name' => $name), array('id_mg' => $id_group));

			if ($result !== false) {
				echo "<h3 class='suc'>".__('Group successfully updated')."</h3>";
			}
			else {
				echo "<h3 class='error'>".__('There was a problem modifying group')."</h3>";
			}
		}
		else {
			echo "<h3 class='error'>".__('Each module group must have a different name')."</h3>";
		}
	}
	else {
		echo "<h3 class='error'>".__('Module group must have a name')."</h3>";
	}
}

/* Delete group */
if ($delete_group) {
	$id_group = (int) get_parameter ('id_group');
	
	$result = db_process_sql_delete('tmodule_group', array('id_mg' => $id_group));
	
	if (! $result)
		echo "<h3 class='error'>".__('There was a problem deleting group')."</h3>"; 
	else
		echo "<h3 class='suc'>".__('Group successfully deleted')."</h3>";
}

$total_groups = db_get_num_rows('SELECT * FROM tmodule_group');

$url = ui_get_url_refresh (array ('offset' => false));

$offset = (int)get_parameter('offset', 0);

ui_pagination($total_groups, $url, $offset);

switch ($config["dbtype"]) {
	case "mysql":
		$sql = "SELECT * 
			FROM tmodule_group
			ORDER BY name ASC
			LIMIT " . $offset . ", " . $config['block_size'];
		break;
	case "postgresql":
		$sql = "SELECT * 
			FROM tmodule_group
			ORDER BY name ASC
			LIMIT " . $config['block_size'] . " OFFSET " . $offset;
		break;
	case "oracle":
		$set = array ();
		$set['limit'] = $config["block_size"];
		$set['offset'] = $offset;
		
		$sql = "SELECT * 
			FROM tmodule_group
			ORDER BY name ASC";
		
		$sql = oracle_recode_query ($sql, $set);
		break;
}

$groups = db_get_all_rows_sql($sql);

$table->width = '98%';

if (!empty($groups)) {
	$table->head = array ();
	$table->head[0] = __('ID');
	$table->head[1] = __('Name');
	$table->head[2] = __('Delete');
	$table->align = array ();
	$table->align[1] = 'left';
	$table->align[2] = 'center';
	$table->data = array ();
	
	foreach ($groups as $id_group ) {
		$data = array ();
		$data[0] = 	$id_group["id_mg"];
		$data[1] = '<strong><a href="index.php?sec=gagente&sec2=godmode/groups/configure_modu_group&id_group='.$id_group["id_mg"].'">' .
			ui_print_truncate_text($id_group["name"], 50).'</a></strong>';
		$data[2] = '<a href="index.php?sec=gagente&sec2=godmode/groups/modu_group_list&id_group='.$id_group["id_mg"].'&delete_group=1" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">' . html_print_image("images/cross.png", true, array("border" => '0')) . '</a>';
		
		array_push ($table->data, $data);
	}
	
	html_print_table ($table);
}
else {
	echo "<div class='nf'>".__('There are no defined module groups')."</div>";
}

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/groups/configure_modu_group">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button (__('Create module group'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';

?>
