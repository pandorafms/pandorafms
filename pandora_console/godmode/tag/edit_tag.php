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
require_once ($config['homedir'].'/include/functions_tags.php');

if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Edit Tag");
	require ("general/noaccess.php");
	
	return;
}

// Get parameters 
$action = (string) get_parameter ("action", "");
$id_tag = (int) get_parameter ("id_tag", 0);
$update_tag = (int) get_parameter ("update_tag", 0);
$create_tag = (int) get_parameter ("create_tag", 0);
$name_tag = (string) get_parameter ("name_tag", "");
$description_tag = (string) get_parameter ("description_tag", "");
$description_tag = io_safe_input(strip_tags(io_safe_output($description_tag)));
$url_tag = (string) get_parameter ("url_tag", "");
$tab = (string) get_parameter ("tab", "list");

$buttons = array(
	'list' => array(
		'active' => false,
		'text' => '<a href="index.php?sec=gmodules&sec2=godmode/tag/tag&tab=list">' . 
			html_print_image ("images/god6.png", true, array ("title" => __('List tags'))) .'</a>'));

$buttons[$tab]['active'] = true;

// Header
ui_print_page_header (__('Tags configuration'), "images/setup.png", false, "", true, $buttons);

// Two actions can performed in this page: update and create tags
// Update tag: update an existing tag
if ($update_tag && $id_tag != 0) {	

	// Erase comma characters on tag name
	$name_tag = str_replace(',', '', $name_tag); 

	$values = array();
	$values['name'] = $name_tag;
	$values['description'] = $description_tag;
	$values['url'] = $url_tag;
	
	$result = false;
	if ($values['name'] != '')
		$result = tags_update_tag($values, 'id_tag = ' . $id_tag);
	
	if ($result === false) {
		echo '<h3 class="error">'.__('Error updating tag').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Successfully updated tag').'</h3>';
	}
}

// Create tag: creates a new tag 
if ($create_tag) {

	$return_create = true;
	
	// Erase comma characters on tag name
	$name_tag = str_replace(',', '', $name_tag); 
	
	$data = array();
	$data['name'] = $name_tag;
	$data['description'] = $description_tag;
	$data['url'] = $url_tag;
	
	// DB insert
	$return_create = false;
	if ($data['name'] != '')
		$return_create = tags_create_tag ($data);

	if ($return_create === false) {
		echo '<h3 class="error">'.__('Error creating tag').'</h3>';
		$action = "new";
	// If create action ends successfully then current action is update
	} else {
		echo '<h3 class="suc">'.__('Successfully created tag').'</h3>';
		$id_tag = $return_create;
		$action = "update";
	}
}

// Form fields are filled here
// Get results when update action is performed
if ($action == "update" && $id_tag != 0){
	$result_tag = tags_search_tag_id($id_tag);
	$name_tag = $result_tag["name"]; 
	$description_tag = $result_tag["description"];
	$url_tag = $result_tag["url"];

// If current action is create (new) or somethig goes wrong fields are filled with void value  
}else{
	$name_tag = "";
	$description_tag = "";
	$url_tag = "";
}
	
// Create/Update tag form 
echo '<form method="post" action="index.php?sec=gmodules&sec2=godmode/tag/edit_tag&action=' . $action . '&id_tag=' . $id_tag . '" enctype="multipart/form-data">';

echo '<div align=left style="width: 98%" class="pandora_form">';

echo "<table border=0 cellpadding=4 cellspacing=4 class=databox width=98%>";
	echo "<tr>";	
		echo "<td align=center>";
		html_print_label (__("Name"),'name');
		echo "</td>";
		echo "<td align=center>"; 
		html_print_input_text ('name_tag', $name_tag); 
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td align=left>";
		html_print_label (__("Description"),'name');
		echo "</td>";
		echo "<td align=center>";
		html_print_input_text ('description_tag', $description_tag);			
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		echo "<td align=left>";
		echo '<b>' . __("Url") . '</b>'; 
		echo ui_print_help_tip (__("Hyperlink to help information that has to exist previously."), true);
		echo "</td>";
		echo "<td align=center>";
		html_print_input_text ('url_tag', $url_tag);
		echo "</td>";
	echo "</tr>";
	echo "<tr>";
		if ($action == "update"){
			echo "<td align=center>";
			html_print_input_hidden ('update_tag', 1);
			echo "</td>";
			echo "<td align=right>";
			html_print_submit_button (__('Update'), 'update_button', false, 'class="sub next"');
			echo "</td>";
		}
		if ($action == "new"){
			echo "<td align=center>";
			html_print_input_hidden ('create_tag', 1);
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
