<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
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


if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Network Profile Management");
	require ("general/noaccess.php");
	exit;
}

require_once ('include/functions_network_components.php');
require_once ("include/functions_modules.php");

ui_print_page_header (__('Module management')." &raquo; ".__('Module template management'), "", false, "", true);

$id_np = get_parameter ("id_np", -1); //Network Profile
$ncgroup = get_parameter ("ncgroup", -1); //Network component group
$ncfilter = get_parameter ("ncfilter", ''); //Network component group
$id_nc = get_parameter ("components", array ());

if (isset ($_GET["delete_module"])) { 
	// Delete module from profile
	$errors = 0;
	foreach ($id_nc as $component) {
		$where = array(
			'id_np' => $id_np,
			'id_nc' => $component);
		$result = db_process_sql_delete('tnetwork_profile_component', $where);
		
		if ($result === false) {
			$errors++;
		}
	}

	ui_print_result_message (($errors < 1),
		__('Successfully deleted module from profile'),
		__('Error deleting module from profile'));
}
elseif (isset ($_GET["add_module"])) {
	// Add module to profile
	$errors = 0;
	foreach ($id_nc as $component) {
		$values = array('id_np' => $id_np, 'id_nc' => $component);
		$result = db_process_sql_insert('tnetwork_profile_component', $values);
		
		if ($result === false) {
			$errors++;
		}
	}
	
	ui_print_result_message (($errors < 1),
		__('Successfully added module to profile'),
		__('Error adding module to profile'));
} 

if (isset ($_GET["create"]) || isset ($_GET["update"])) {
	//Submitted form
	$name = get_parameter_post ("name");
	$description = get_parameter_post ("description");
	if ($name != "") {		
		if ($id_np > 0) {
			//Profile exists
			$values = array(
				'name' => $name,
				'description' => $description);
			$result = db_process_sql_update('tnetwork_profile', $values, array('id_np' => $id_np));
		
			ui_print_result_message ($result !== false,
				__('Successfully updated network profile'),
				__('Error updating network profile'));
		}
		else {
			//Profile doesn't exist
			$values = array('name' => $name, 'description' => $description);
			$result = db_process_sql_insert('tnetwork_profile', $values);
			
			ui_print_result_message ($result,
				__('Successfully added network profile'),
				__('Error adding network profile'));
			$id_np = (int) $result; //Will return either 0 (in case of error) or an int
		}
	} else {
		ui_print_result_message(false, "", _("Cannot create a template without name"));
	}	

}
elseif ($id_np > 0) {
	//Profile exists
	$row = db_get_row ("tnetwork_profile", "id_np", $id_np);
		
	$description = $row["description"];
	$name = $row["name"];

}
else {
	//Profile has to be created
	$description = "";
	$name = "";
}


if ($id_np < 1) {
	echo '<form name="new_temp" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'&create=1">';
}
else {
	echo '<form name="mod_temp" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'&update='.$id_np.'">';
}

echo '<table width="98%" cellpadding="4" cellspacing="4" class="databox_color">';

echo '<tr><td class="datos">'.__('Name').'</td><td class="datos">';
html_print_input_text ("name", $name, '', 63);
echo '</td></tr>';

echo '<tr><td class="datos2">'.__('Description').'</td>';
echo '<td class="datos2">';
html_print_textarea ("description", 2, 60, $description);
echo "</td></tr>";
echo '<tr><td></td><td style="text-align:right;">';
if ($id_np > 0) {
	html_print_submit_button (__("Update"), "updbutton", false, 'class="sub upd"');
}
else {
	html_print_submit_button (__("Create"), "crtbutton", false, 'class="sub wand"');
}
echo "</td></tr></table></form>";

if ($id_np > 0) {
	// Show associated modules, allow to delete, and to add
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("SELECT npc.id_nc AS component_id, nc.name, nc.type, nc.description, nc.id_group AS `group`
				FROM tnetwork_profile_component AS npc, tnetwork_component AS nc 
				WHERE npc.id_nc = nc.id_nc AND npc.id_np = %d", $id_np);
			break;
		case "postgresql":
			$sql = sprintf ("SELECT npc.id_nc AS component_id, nc.name, nc.type, nc.description, nc.id_group AS \"group\"
				FROM tnetwork_profile_component AS npc, tnetwork_component AS nc 
				WHERE npc.id_nc = nc.id_nc AND npc.id_np = %d", $id_np);
			break;
		case "oracle":
			$sql = sprintf ("SELECT npc.id_nc AS component_id, nc.name, nc.type, nc.description, nc.id_group AS \"group\"
				FROM tnetwork_profile_component npc, tnetwork_component nc 
				WHERE npc.id_nc = nc.id_nc AND npc.id_np = %d", $id_np);
			break;
	}
	
	$result = db_get_all_rows_sql ($sql);

	if (empty ($result)) {
		echo '<div style="width:550px;" class="error">' . __("No modules for this profile") . '</div>';
		$result = array ();
	}

	$table->head = array ();
	$table->data = array ();
	$table->align = array ();
	$table->width = '98%';
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->class = "databox";
	
	$table->head[0] = __('Module name');
	$table->head[1] = __('Type');
	$table->align[1] = "center";
	$table->head[2] = __('Description');
	$table->head[3] = __('Group');
	$table->align[3] = "center";
	$table->head[4] = html_print_checkbox_extended ('allbox', '', false, false, 'CheckAll();', '', true);
	$table->align[4] = "center";
	
	foreach ($result as $row) {
		$data = array ();
		$data[0] = $row["name"];
		$data[1] = html_print_image("images/".modules_show_icon_type($row["type"]), true, array("border" => '0'));
		$data[2] = mb_strimwidth(io_safe_output($row["description"]),0,150,"...");
		$data[3] = network_components_get_group_name ($row["group"]);
		$data[4] = html_print_checkbox ("components[]", $row["component_id"], false, true);
		array_push ($table->data, $data);
	}

	if (!empty ($table->data)) {
		echo '<form name="component_delete" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'&delete_module=1">';
		html_print_table ($table);
		echo '<div style="width:'.$table->width.'; text-align:right">';
		html_print_submit_button (__('Delete'), "delbutton", false, 'class="sub delete" onClick="if (!confirm(\'Are you sure?\')) return false;"');
		echo '</div></form>';
	}
	unset ($table);
	
	echo "<h4 style='margin-top:0px !important;'>".__('Add modules')."</h4>";
	
	unset($table);
	
	$table->head = array ();
	$table->data = array ();
	$table->align = array ();
	$table->width = '98%';
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->class = "databox";
	
	//The form to submit when adding a list of components
	
	$filter = '<form name="filter_component" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&ncgroup='.$ncgroup.'&id_np='.$id_np.'#filter">';
	$filter .= html_print_input_text ('ncfilter', $ncfilter, '', 50, 255, true);
	$filter .= '&nbsp;'.html_print_submit_button (__('Filter'), 'ncgbutton', false, 'class="sub search"', true);
	$filter .= '</form>';
	
	$group_filter = '<form name="filter_group" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'#filter">';
	$group_filter .= '<div style="width:540px"><a name="filter"></a>';
	$result = db_get_all_rows_in_table ("tnetwork_component_group","name");
	if($result === false) {
		$result = array();
	}
	
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
	
	$group_filter .= html_print_select ($groups_compound, "ncgroup", $ncgroup, 'javascript:this.form.submit();', __('Group')." - ".__('All'), -1, true, false, true, '" style="width:350px');

	$group_filter .= '</div></form>';
	
	if ($ncgroup > 0) {
		$sql = sprintf ("SELECT id_nc, name, id_group FROM tnetwork_component WHERE id_group = %d AND name LIKE '%".$ncfilter."%' ORDER BY name", $ncgroup);
	}
	else {
		$sql = "SELECT id_nc, name, id_group FROM tnetwork_component WHERE name LIKE '%".$ncfilter."%' ORDER BY name"; 
	}

	$result = db_get_all_rows_sql ($sql);
	$components = array ();
	if ($result === false)
		$result = array ();

	foreach ($result as $row) {
		$components[$row["id_nc"]] = $row["name"];
	}

	$components_select = '<form name="add_module" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'&add_module=1">';
	$components_select .= html_print_select ($components, "components[]", $id_nc, '', '', -1, true, true, false, '" style="width:350px');
	
	$table->data[0][0] = __('Filter');
	$table->data[0][1] = $filter;
	$table->data[1][0] = __('Group');
	$table->data[1][1] = $group_filter;
	$table->data[2][0] = __('Components');
	$table->data[2][1] = $components_select;
	
	html_print_table($table);
	
	echo '<div style="width:'.$table->width.'; text-align:right">';
	html_print_submit_button (__('Add'), 'crtbutton', false, 'class="sub wand"');
	echo '</div></form>';	
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
