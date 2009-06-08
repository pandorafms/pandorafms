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
require_once ("include/config.php");

check_login ();


if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Network Profile Management");
	require ("general/noaccess.php");
	exit;
}

$id_np = get_parameter ("id_np", -1); //Network Profile
$ncgroup = get_parameter ("ncgroup", -1); //Network component group
$id_nc = get_parameter ("components", array ());

if (isset ($_GET["delete_module"])) { 
	// Delete module from profile
	$errors = 0;
	foreach ($id_nc as $component) {
		$sql = sprintf ("DELETE FROM tnetwork_profile_component WHERE id_np = %d AND id_nc = %d", $id_np, $component);
		$result = process_sql ($sql);
		if ($result === false) {
			$errors++;
		}
	}

	print_result_message (($errors < 1),
		__('Successfully deleted module from profile'),
		__('Error deleting module from profile'));
} elseif (isset ($_GET["add_module"])) {
	// Add module to profile
	$errors = 0;
	foreach ($id_nc as $component) {
		$sql = sprintf ("INSERT INTO tnetwork_profile_component (id_np,id_nc) VALUES (%d, %d)", $id_np, $component);
		$result = process_sql ($sql);
		if ($result === false) {
			$errors++;
		}
	}
	
	print_result_message (($errors < 1),
		__('Successfully added module to profile'),
		__('Error adding module to profile'));
} 

if (isset ($_GET["create"]) || isset ($_GET["update"])) {
	//Submitted form
	$name = get_parameter_post ("name");
	$description = get_parameter_post ("description");
	
	if ($id_np > 0) {
		//Profile exists
		$sql = sprintf ("UPDATE tnetwork_profile SET name = '%s', description = '%s' WHERE id_np = %d", $name, $description, $id_np); 		
		$result = process_sql ($sql);
		print_result_message ($result,
			__('Successfully updated network profile'),
			__('Error updating network profile'));
	} else {
		//Profile doesn't exist
		$sql = sprintf ("INSERT INTO tnetwork_profile (name, description) VALUES ('%s', '%s')", $name, $description);
		$result = process_sql ($sql, "insert_id");
		print_result_message ($result,
			__('Successfully added network profile'),
			__('Error adding network profile'));
		$id_np = (int) $result; //Will return either 0 (in case of error) or an int
	}

} elseif ($id_np > 0) {
	//Profile exists
	$row = get_db_row ("tnetwork_profile", "id_np", $id_np);
		
	$description = $row["description"];
	$name = $row["name"];

} else {
	//Profile has to be created
	$description = "";
	$name = "";
}

echo "<h2>".__('Module management')." &raquo; ".__('Module template management')."</h2>";

if ($id_np < 1) {
	echo '<form name="new_temp" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'&create=1">';
} else {
	echo '<form name="mod_temp" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'&update='.$id_np.'">';
}

echo '<table width="550" cellpadding="4" cellspacing="4" class="databox_color">';

echo '<tr><td class="datos">'.__('Name').'</td><td class="datos">';
print_input_text ("name", $name, '', 63);
echo '</td></tr>';

echo '<tr><td class="datos2">'.__('Description').'</td>';
echo '<td class="datos2">';
print_textarea ("description", 2, 60, $description);
echo "</td></tr>";
echo '<tr><td></td><td style="text-align:right;">';
if ($id_np > 0) {
	print_submit_button (__("Update"), "updbutton", false, 'class="sub upd"');
} else {
	print_submit_button (__("Create"), "crtbutton", false, 'class="sub wand"');
}
echo "</td></tr></table></form>";

if ($id_np > 0) {
	// Show associated modules, allow to delete, and to add
	$sql = sprintf ("SELECT npc.id_nc AS component_id, nc.name, nc.type, nc.description, nc.id_module_group AS `group`
		FROM tnetwork_profile_component AS npc, tnetwork_component AS nc 
		WHERE npc.id_nc = nc.id_nc AND npc.id_np = %d", $id_np);
	
	$result = get_db_all_rows_sql ($sql);

	if (empty ($result)) {
		echo '<div style="width:550px;" class="error">'.__("No modules for this profile").'</div>';
		$result = array ();
	}

	$table->head = array ();
	$table->data = array ();
	$table->align = array ();
	$table->width = 550;
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->class = "databox";
	
	$table->head[0] = __('Module name');
	$table->head[1] = __('Type');
	$table->align[1] = "center";
	$table->head[2] = __('Description');
	$table->head[3] = __('Group');
	$table->align[3] = "center";
	$table->head[4] = print_checkbox_extended ('allbox', '', false, false, 'CheckAll();', '', true);
	$table->align[4] = "center";
	
	foreach ($result as $row) {
		$data = array ();
		$data[0] = $row["name"];
		$data[1] = '<img src="images/'.show_icon_type($row["type"]).'" border="0" />';
		$data[2] = substr($row["description"],0,30);
		$data[3] = get_network_component_group_name ($row["group"]);
		$data[4] = print_checkbox ("components[]", $row["component_id"], false, true);
		array_push ($table->data, $data);
	}

	if (!empty ($table->data)) {
		echo '<form name="component_delete" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'&delete_module=1">';
		print_table ($table);
		echo '<div style="width:540px; text-align:right">';
		print_submit_button (__('Delete'), "delbutton", false, 'class="sub delete" onClick="if (!confirm(\'Are you sure?\')) return false;"');
		echo '</div></form>';
	}
	unset ($table);
	
	echo "<h3>".__('Add Modules')."</h3>";
	
	//Here should be a form to filter group

	//The form to submit when adding a list of components
	echo '<form name="filter_group" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'#filter">';
	echo '<div style="width:540px"><a name="filter"></a>';
	$result = get_db_all_rows_in_table ("tnetwork_component_group","name");
	
	//2 arrays. 1 with the groups, 1 with the groups by parent
	$groups = array ();
	$groups_compound = array ();
	foreach ($result as $row) {
		$groups[$row["id_sg"]] = $row["name"];	
	}

	foreach ($result as $row) {
		$groups_compound[$row["id_sg"]] = '';
		if ($row["parent"] > 1) {
			$groups_compound[$row["id_sg"]] = $groups[$row["parent"]]." / ";
		}
		$groups_compound[$row["id_sg"]] .= $row["name"];
	}
	
	print_select ($groups_compound, "ncgroup", $ncgroup, 'javascript:this.form.submit();', __('Group')." - ".__('All'), -1, false, false, true, '" style="width:350px');
	echo '<noscript>';
	print_submit_button (__('Filter'), 'ncgbutton', false, 'class="sub search"');
	echo '</noscript></div></form>';
	
	echo '<form name="add_module" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'&add_module=1">';
	echo '<div style="width:540px">';
	if ($ncgroup > 0) {
		$sql = sprintf ("SELECT id_nc, name, id_group FROM tnetwork_component WHERE id_group = %d ORDER BY name", $ncgroup);
	} else {
		$sql = "SELECT id_nc, name, id_group FROM tnetwork_component ORDER BY name"; 
	}

	$result = get_db_all_rows_sql ($sql);
	$components = array ();
	if ($result === false)
		$result = array ();

	foreach ($result as $row) {
		$components[$row["id_nc"]] = $row["name"];
	}

	print_select ($components, "components[]", $id_nc, '', '', -1, false, true, false, '" style="width:350px');
	echo "&nbsp;&nbsp;";
	print_submit_button (__('Add'), 'crtbutton', false, 'class="sub wand"');
	echo "</div></form>";
}

?>
<script type="text/javascript">
/* <![CDATA[ */
function CheckAll() {
	for (var i = 0; i < document.component_delete.elements.length; i++) {
	
		var e = document.component_delete.elements[i];
		if (e.type == 'checkbox' && e.name != 'allbox')
			e.checked = !e.checked;
	}
}
/* ]]> */
</script>
