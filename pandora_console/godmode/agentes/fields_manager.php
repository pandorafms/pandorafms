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
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	return;
}

// Header
print_page_header (__("Agents custom fields manager"), "images/note.png", false, "", true, "");

$create_field = (bool) get_parameter ('create_field');
$update_field = (bool) get_parameter ('update_field');
$delete_field = (bool) get_parameter ('delete_field');
$id_field = (int) get_parameter ('id_field', 0);	
$name = (string) get_parameter ('name', '');
$display_on_front = (int) get_parameter ('display_on_front', 0);

/* Create field */
if ($create_field) {
	/*Check if name field is empty*/
	if ($name != "") {
		$sql = sprintf ('INSERT INTO tagent_custom_fields (name, display_on_front) 
				VALUES ("%s", "%d")',
				$name, $display_on_front);
		$result = mysql_query ($sql);
	} else {
		$result = false;
	}
	
	if ($result) {
		echo "<h3 class='suc'>".__('Field successfully created')."</h3>"; 
	} else {
		echo "<h3 class='error'>".__('There was a problem creating field')."</h3>";	}
}

/* Update field */
if ($update_field) {
	/*Check if name field is empty*/
	if( $name != "") {	
		$sql = sprintf ('UPDATE tagent_custom_fields SET name = "%s",
				display_on_front = %d
				WHERE id_field = %d',
				$name, $display_on_front, $id_field);
		$result = process_sql ($sql);
	} else {
		$result = false;
	}
	
	if ($result !== false) {
		echo "<h3 class='suc'>".__('Field successfully updated')."</h3>";
	} else {
		echo "<h3 class='error'>".__('There was a problem modifying field')."</h3>";
	}
}

/* Delete field */
if ($delete_field) {	
	$sql = sprintf ('DELETE FROM tagent_custom_fields WHERE id_field = %d', $id_field);
	$result = process_sql ($sql);
	
	if (!$result)
		echo "<h3 class='error'>".__('There was a problem deleting field')."</h3>"; 
	else
		echo "<h3 class='suc'>".__('Field successfully deleted')."</h3>";	 
}


$table->width = '65%';
$table->head = array ();
$table->head[0] = __('Field');
$table->head[1] = __('Display on front').print_help_tip (__('The fields with display on front enabled will be displayed into the agent details'), true);
$table->head[2] = __('Actions');
$table->align = array ();
$table->align[1] = 'center';
$table->align[2] = 'center';
$table->data = array ();

$fields = get_db_all_fields_in_table('tagent_custom_fields');

if($fields === false) $fields = array();

foreach ($fields as $field) {
	
	$data[0] = '<b>'.$field['name'].'</b>';

	if($field['display_on_front']) {
		$data[1] = print_image('images/tick.png', true);
	}else {
		$data[1] = print_image('images/delete.png', true);
	}
		
	$data[2] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configure_field&id_field='.$field['id_field'].'"><img border="0" src="images/config.png" alt="' . __('Edit') . '" title="' . __('Edit') . '" /></a>';
	$data[2] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/fields_manager&delete_field=1&id_field='.$field['id_field'].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;"><img alt="' . __('Delete') . '" alt="' . __('Delete') . '" border="0" src="images/cross.png"></a>';
	
	array_push ($table->data, $data);
}

print_table ($table);

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configure_field">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Create field'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';

?>
