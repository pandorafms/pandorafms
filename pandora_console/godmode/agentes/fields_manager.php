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

if (!check_acl($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	
	return;
}

// Header
ui_print_page_header (__("Agents custom fields manager"), "images/note.png", false, "", true, "");

$create_field = (bool) get_parameter ('create_field');
$update_field = (bool) get_parameter ('update_field');
$delete_field = (bool) get_parameter ('delete_field');
$id_field = (int) get_parameter ('id_field', 0);	
$name = (string) get_parameter ('name', '');
$display_on_front = (int) get_parameter ('display_on_front', 0);

/* Create field */
if ($create_field) {
	/*Check if name field is empty*/
	if ($name == ""){
		echo "<h3 class='error'>".__('The name must not be empty')."</h3>";
	} else if ($name == db_get_value ('name', 'tagent_custom_fields', 'name', $name)) {
		echo "<h3 class='error'>".__('The name must be unique')."</h3>";
	} else {
		$result = db_process_sql_insert('tagent_custom_fields', array('name' => $name, 'display_on_front' => $display_on_front));
		echo "<h3 class='suc'>".__('Field successfully created')."</h3>";
	}
}

/* Update field */
if ($update_field) {
	/*Check if name field is empty*/
	if( $name != "") {	
		$values = array('name' => $name, 'display_on_front' => $display_on_front);
		
		$result = db_process_sql_update('tagent_custom_fields', $values, array('id_field' => $id_field));
	}
	else {
		$result = false;
	}
	
	if ($result !== false) {
		echo "<h3 class='suc'>".__('Field successfully updated')."</h3>";
	}
	else {
		echo "<h3 class='error'>".__('There was a problem modifying field')."</h3>";
	}
}

/* Delete field */
if ($delete_field) {	
	$result = db_process_sql_delete('tagent_custom_fields', array('id_field' => $id_field));
	
	if (!$result)
		echo "<h3 class='error'>".__('There was a problem deleting field')."</h3>"; 
	else
		echo "<h3 class='suc'>".__('Field successfully deleted')."</h3>";	 
}

$fields = db_get_all_fields_in_table('tagent_custom_fields');

	$table->width = '98%';
if ($fields) {
	$table->head = array ();
	$table->head[0] = __('Field');
	$table->head[1] = __('Display on front').ui_print_help_tip (__('The fields with display on front enabled will be displayed into the agent details'), true);
	$table->head[2] = __('Actions');
	$table->align = array ();
	$table->align[1] = 'center';
	$table->align[2] = 'center';
	$table->data = array ();
}
else {
	echo '<div class="nf">'. __('No fields defined') .'</div>';
}

if($fields === false) $fields = array();

foreach ($fields as $field) {
	
	$data[0] = '<b>'.$field['name'].'</b>';

	if($field['display_on_front']) {
		$data[1] = html_print_image('images/tick.png', true);
	}
	else {
		$data[1] = html_print_image('images/delete.png', true);
	}
		
	$data[2] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configure_field&id_field='.$field['id_field'].'">' . html_print_image("images/config.png", true, array("alt" => __('Edit'), "title" => __('Edit'), "border" => '0')) . '</a>';
	$data[2] .= '&nbsp;&nbsp;<a href="index.php?sec=gagente&sec2=godmode/agentes/fields_manager&delete_field=1&id_field='.$field['id_field'].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">' . html_print_image("images/cross.png", true, array("alt" => __('Delete'), "title" => __('Delete'), "border" => '0')) . '</a>';
	
	array_push ($table->data, $data);
}

if($fields)
	html_print_table ($table);

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configure_field">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button (__('Create field'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';

?>
