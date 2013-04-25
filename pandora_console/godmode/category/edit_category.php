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

//Include functions code
require_once ($config['homedir'].'/include/functions_categories.php');

if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Edit Category");
	require ("general/noaccess.php");
	
	return;
}

// Get parameters 
$action = (string) get_parameter ("action", "");
$id_category = (int) get_parameter ("id_category", 0);
$update_category = (int) get_parameter ("update_category", 0);
$create_category = (int) get_parameter ("create_category", 0);
$name_category = (string) get_parameter ("name_category", "");
$tab = (string) get_parameter ("tab", "list");

$buttons = array(
	'list' => array(
		'active' => false,
		'text' => '<a href="index.php?sec=gmodules&sec2=godmode/category/category&tab=list&pure='.(int)$config['pure'].'">' . 
			html_print_image ("images/list.png", true, array ("title" => __('List categories'))) .'</a>'));

$buttons[$tab]['active'] = false;

// Header
if (defined('METACONSOLE')) {
	ui_meta_print_header(__('Categories configuration'), __('Editor'), $buttons);
}
else {
	ui_print_page_header (__('Categories configuration'), "images/gm_modules.png", false, "", true, $buttons);
}


// Two actions can performed in this page: update and create categories
// Update category: update an existing category
if ($update_category && $id_category != 0) {
	$values = array();
	$values['name'] = $name_category;
	
	$result = false;
	if ($values['name'] != '')
		$result = db_process_sql_update('tcategory', $values, array('id' => $id_category));
	
	if ($result === false) {
		db_pandora_audit("Category management", "Fail try to update category #$id_category");
		ui_print_error_message(__('Error updating category'));
	}
	else {
		db_pandora_audit("Category management", "Update category #$id_category");
		ui_print_success_message(__('Successfully updated category'));
	}
}

// Create category: creates a new category 
if ($create_category) {
	
	$return_create = true;
	
	$values = array();
	$values['name'] = $name_category;
	
	// DB insert
	$return_create = false;
	if ($values['name'] != '')
		$return_create = db_process_sql_insert('tcategory', $values);
		
	if ($return_create === false) {
		db_pandora_audit("Category management", "Fail try to create category");
		ui_print_error_message(__('Error creating category'));
		$action = "new";
	// If create action ends successfully then current action is update
	}
	else {
		db_pandora_audit("Category management", "Create category #$return_create");
		ui_print_success_message(__('Successfully created category'));
		$id_category = $return_create;
		$action = "update";
	}
}

// Form fields are filled here
// Get results when update action is performed
if ($action == "update" && $id_category != 0) {
	$result_category = db_get_row_filter('tcategory', array('id' => $id_category));
	$name_category = $result_category["name"]; 
} // If current action is create (new) or somethig goes wrong fields are filled with void value
else {
	$name_category = "";
}

// Create/Update category form 
echo '<form method="post" action="index.php?sec=gmodules&sec2=godmode/category/edit_category&action=' . $action . '&id_category=' . $id_category . '&pure='.(int)$config['pure'].'" enctype="multipart/form-data">';

echo '<div align=left style="width: 98%" class="pandora_form">';

echo "<table border=0 cellpadding=4 cellspacing=4 class=databox width=98%>";
	echo "<tr>";
		echo "<td align=center>";
		html_print_label (__("Name"),'name');
		echo "</td>";
		echo "<td align=center>"; 
		html_print_input_text ('name_category', $name_category); 
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		if ($action == "update") {
			echo "<td align=center>";
			html_print_input_hidden ('update_category', 1);
			echo "</td>";
			echo "<td align=right>";
			html_print_submit_button (__('Update'), 'update_button', false, 'class="sub next"');
			echo "</td>";
		}
		if ($action == "new") {
			echo "<td align=center>";
			html_print_input_hidden ('create_category', 1);
			echo "</td>";
			echo "<td align=right>";
			html_print_submit_button (__('Create'), 'create_button', false, 'class="sub next"');
			echo "</td>";
		}
	echo "</tr>";
echo "</table>";
echo '</div>';
echo '</form>';

?>
