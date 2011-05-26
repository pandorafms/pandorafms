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
	db_pandora_audit("ACL Violation", "Trying to access Edit Skin");
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
$url_tag = (string) get_parameter ("url_tag", "");

// Header
ui_print_page_header (__('Tags configuration'), "images/comments.png", false, "", true);

// Two actions can performed in this page: update and create tags
// Update tag: update an existing tag
if ($update_tag && $id_tag != 0) {	
	$values = array();
	$values['name'] = $name_tag;
	$values['description'] = $description_tag;
	$values['url'] = $url_tag;
	
	$result = tags_update_tag($values);
	
	if ($result === false) {
		echo '<h3 class="error">'.__('Error updating tag').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Successfully updated skin').'</h3>';
	}
}

// Create tag: creates a new tag 
if ($create_tag) {

	$return_create = true;
	
	$data = array();
	$data['name'] = $name_tag;
	$data['description'] = $description_tag;
	$data['url'] = $url_tag;
	
	// DB insert
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
echo '<form method="post" action="index.php?sec=gtag&sec2=godmode/tag/edit_tag&action=' . $action . '&id_tag=' . $id_tag . '" enctype="multipart/form-data">';

echo '<div align=left style="width: 98%" class="pandora_form">';

echo "<table border=0 cellpadding=4 cellspacing=4 class=databox width=85%>";
	echo "<tr>";
		echo "<td>";
		if ($action == "update"){ 
			echo '<h3>'.__("Edit tag").'</h3>';
		}
		if ($action == "new"){
			echo '<h3>'.__("New tag").'</h3>';
		}
		echo "</td>";
	echo "</tr>";
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
