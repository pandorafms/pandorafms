<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.


global $config;

include_once("include/functions_ui.php");
include_once("include/functions_netflow.php");
include_once ("include/functions_users.php");
include_once ("include/functions_groups.php");

check_login ();

if (! check_acl ($config["id_user"], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$id = (int) get_parameter ('id');
//$name = db_get_value('id_name', 'tnetflow_filter', 'id_sg', $id);
$update = (string)get_parameter('update', 0);
$create = (string)get_parameter('create', 0);
		
$buttons['edit'] = '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_manage">'
		. html_print_image ("images/edit.png", true, array ("title" => __('Option list')))
		. '</a>';
	
//Header
ui_print_page_header (__('Netflow Options'), "images/networkmap/so_cisco_new.png", false, "", true, $buttons);

if ($id) {
	$option = netflow_options_get_options ($id);
	$name = $option['id_name'];
	$description = $option['description'];
	$path = $option['path'];
	$port = $option['port'];

} else {
	$name = '';
	$description = '';
	$path = '';
	$port = '';
}

if ($update) {
	$name = (string) get_parameter ('name');
	$description = (int) get_parameter ('description','');
	$path = get_parameter('path','');
	$port = get_parameter('port','');
	
	if ($name == '') {
                ui_print_error_message (__('Not updated. Blank name'));
        } else {
		$result = db_process_sql_update ('tnetflow_options',
			array ('id_option' => $id,
				'id_name' => $name,
				'description' => $description,
				'path' => $path,
				'port' => $port
				),
			array ('id_option' => $id));
			
		ui_print_result_message ($result,
			__('Successfully updated'),
			__('Not updated. Error updating data'));
	}
}

if ($create){
	$name = (string) get_parameter ('name');
	$description = (string) get_parameter ('description','');
	$path = get_parameter('path','');
	$port = get_parameter('port','');

		if($name == db_get_value('id_name', 'tnetflow_options', 'id_name', $name)){	
			$result = false;
		} else {
			$values = array (
				'id_name'=>$name,
				'description' => $description,
				'path'=>$path,
				'port'=>$port
			);
			$result = db_process_sql_insert('tnetflow_options', $values);
		}
		if ($result === false)
				echo '<h3 class="error">'.__ ('Error creating filter').'</h3>';
			else
				echo '<h3 class="suc">'.__ ('Option created successfully').'</h3>';
}

$table->width = '80%';
$table->border = 0;
$table->cellspacing = 3;
$table->cellpadding = 5;
$table->class = "databox_color";
$table->style[0] = 'vertical-align: top;';

$table->data = array ();
	
$table->data[0][0] = '<b>'.__('Name').'</b>';
$table->data[0][1] = html_print_input_text ('name', $name, false, 50, 80, true);

$table->data[1][0] = '<b>'.__('Description').'</b>';
$table->data[1][1] = html_print_textarea ('description', 2, 65, $description, '', true);

$table->data[2][0] = '<b>'.__('Path').'</b>';
//$table->data[2][1] = html_print_input_text ('path', $config['netflow_path'], false, 50, 200, true);
$table->data[2][1] = html_print_input_text ('path', $path, false, 50, 200, true);
	
$table->data[3][0] = '<b>'.__('Port').'</b>';
$table->data[3][1] = html_print_input_text ('port', $port, false, 10, 80, true);


echo '<form method="post" action="index.php?sec=netf&sec2=godmode/netflow/nf_option_form">';
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	html_print_input_hidden ('update', 1);
	html_print_input_hidden ('id', $id);
	html_print_submit_button (__('Update'), 'crt', false, 'class="sub upd"');
} else {
	html_print_input_hidden ('create', 1);
	html_print_submit_button (__('Create'), 'crt', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';
?>
