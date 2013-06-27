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


if (is_ajax ()) {
	$get_plugin_description = get_parameter('get_plugin_description');
	$id_plugin = get_parameter('id_plugin');
	
	$description = db_get_value_filter('description', 'tplugin', array('id' => $id_plugin));
	$preload = io_safe_output($description);
	$preload = str_replace ("\n", "<br>", $preload);
	
	echo $preload;
	return;
}

// Load global vars
global $config;

require_once ("include/functions_filemanager.php");

check_login ();

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Plugin Management");
	require ("general/noaccess.php");
	return;
}

$view = get_parameter ("view", "");
$create = get_parameter ("create", "");
$filemanager = (bool)get_parameter("filemanager", false);
$plugin_command = get_parameter('plugin_command', '');

if ($view != "") {
	$form_id = $view;
	$plugin = db_get_row ("tplugin", "id", $form_id);
	$form_name = $plugin["name"];
	$form_description = $plugin["description"];
	$form_max_timeout = $plugin ["max_timeout"];
	if (empty($plugin_command))
		$form_execute = $plugin ["execute"];
	else
		$form_execute = $plugin_command;
	$form_net_dst_opt = $plugin ["net_dst_opt"];
	$form_net_port_opt = $plugin ["net_port_opt"];
	$form_user_opt = $plugin ["user_opt"];
	$form_pass_opt = $plugin ["pass_opt"];
	$form_plugin_type = $plugin ["plugin_type"];
}

if ($create != "") {
	$form_id = 0;
	$form_name = "";
	$form_description = "";
	$form_max_timeout = "";
	$form_execute = $plugin_command;
	$form_net_dst_opt = "";
	$form_net_port_opt = "";
	$form_user_opt = "";
	$form_pass_opt = "";
	$form_plugin_type = 0;
}
//END LOAD VALUES

// =====================================================================
// INIT FILEMANAGER
// =====================================================================
if ($filemanager) {
	
	$id_plugin = (int)get_parameter('id_plugin', 0);
	
	
	/* Add custom directories here */
	$fallback_directory = "attachment/plugin";
	
	$directory = (string) get_parameter ('directory', $fallback_directory);
	
	// A miminal security check to avoid directory traversal
	if (preg_match ("/\.\./", $directory))
		$directory = $fallback_directory;
	if (preg_match ("/^\//", $directory))
		$directory = $fallback_directory;
	if (preg_match ("/^manager/", $directory))
		$directory = $fallback_directory;
	
	$banned_directories['include'] = true;
	$banned_directories['godmode'] = true;
	$banned_directories['operation'] = true;
	$banned_directories['reporting'] = true;
	$banned_directories['general'] = true;
	$banned_directories[ENTERPRISE_DIR] = true;
	
	if (isset ($banned_directories[$directory]))
		$directory = $fallback_directory;
	
	$real_directory = realpath ($config['homedir'] . '/' . $directory);
	
	echo '<h4>' . __('Index of %s', $directory) . '</h4>';
	
	$chunck_url = '&view=' . $id_plugin;
	if ($id_plugin == 0) {
		$chunck_url = '&create=1';
	}
	
	filemanager_file_explorer($real_directory,
		$directory,
		'index.php?sec=gservers&sec2=godmode/servers/plugin&filemanager=1&id_plugin=' . $id_plugin,
		$fallback_directory,
		false,
		false,
		'index.php?sec=gservers&sec2=godmode/servers/plugin' . $chunck_url . '&plugin_command=[FILE_FULLPATH]&id_plugin=' . $id_plugin,
		true,
		0775);
	
	
	return;
}

// =====================================================================
// END FILEMANAGER
// =====================================================================

// SHOW THE FORM
// =====================================================================


if (($create != "") OR ($view != "")) {
	
	if ($create != "")
		ui_print_page_header (__('Plugin creation') . ui_print_help_icon("plugin_definition", true), "", false, "", true);
	else {
		ui_print_page_header (__('Plugin update') . ui_print_help_icon("plugin_definition", true), "", false, "", true);
		$plugin_id = get_parameter ("view","");
	}
	
	
	if ($create == "")
		echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&update_plugin=$plugin_id'>";
	else
		echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&create_plugin=1'>";
	
	echo '<table width="98%" cellspacing="4" cellpadding="4" class="databox_color">';
	
	echo '<tr>';
	echo '<td class="datos">' . __('Name') . '</td>';
	echo '<td class="datos">';
	echo '<input type="text" name="form_name" size=100 value="'.$form_name.'"></td>';
	echo '</td>';
	echo '</tr>';
	
	echo '<tr>';
	echo '<td class="datos2">' . __('Plugin command') . '</td>';
	echo '<td class="datos2">';
	echo '<input type="text" name="form_execute" size=45 value="'.$form_execute.'">';
	echo ' <a href="index.php?sec=gservers&sec2=godmode/servers/plugin&filemanager=1&id_plugin=' . $form_id . '" style="vertical-align: bottom;">';
	html_print_image('images/file.png');
	echo '</a>';
	echo '</td>';
	echo '</tr>';
	
	echo '<tr>';
	echo '<td class="datos2">' . __('Plugin type') . '</td>';
	echo '<td class="datos2">';
	$fields[0]= __("Standard");
	$fields[1]= __("Nagios");
	html_print_select ($fields, "form_plugin_type", $form_plugin_type);
	echo '</td>';
	echo '</tr>';
	
	echo '<tr>';
	echo '<td class="datos">'  . __('Max. timeout') . '</td>';
	echo '<td class="datos">';
	echo '<input type="text" name="form_max_timeout" size=5 value="'.$form_max_timeout.'"></td>';
	echo '</td>';
	echo '</tr>';
	
	echo '<tr>';
	echo '<td class="datos2">' . __('IP address option') . '</td>';
	echo '<td class="datos2">';
	echo '<input type="text" name="form_net_dst_opt" size=15 value="'.$form_net_dst_opt.'"></td>';
	echo '</td>';
	echo '</tr>';
	
	echo '<tr><td class="datos">'.__('Port option');
	echo '<td class="datos">';
	echo '<input type="text" name="form_net_port_opt" size=5 value="'.$form_net_port_opt.'"></td>';
	
	
	echo '<tr><td class="datos2">'.__('User option');
	echo '<td class="datos2">';
	echo '<input type="text" name="form_user_opt" size=15 value="'.$form_user_opt.'"></td>';
	
	echo '<tr><td class="datos">'.__('Password option');
	echo '<td class="datos">';
	echo '<input type="text" name="form_pass_opt" size=15 value="'.$form_pass_opt.'"></td>';
	
	echo '<tr><td class="datos2">'.__('Description').'</td>';
	echo '<td class="datos2"><textarea name="form_description" cols="50" rows="4">';
	echo $form_description;
	echo '</textarea></td></tr>';
	
	echo '</table>';
	echo '<table width="98%">';
	echo '<tr><td align="right">';
	
	if ($create != "") {
		echo "<input name='crtbutton' type='submit' class='sub wand' value='" .
			__('Create') . "'>";
	}
	else {
		echo "<input name='uptbutton' type='submit' class='sub upd' value='" .
			__('Update') . "'>";
	}
	echo '</form></table>';
	
}
else {
	ui_print_page_header (__('Plugins registered in Pandora FMS'), "", false, "", true);
	
	
	// Update plugin
	if (isset($_GET["update_plugin"])) { // if modified any parameter
		$plugin_id = get_parameter ("update_plugin", 0);
		$plugin_name = get_parameter ("form_name", "");
		$plugin_description = get_parameter ("form_description", "");
		$plugin_max_timeout = get_parameter ("form_max_timeout", "");
		$plugin_execute = get_parameter ("form_execute", "");
		$plugin_net_dst_opt = get_parameter ("form_net_dst_opt", "");
		$plugin_net_port_opt = get_parameter ("form_net_port_opt", "");
		$plugin_user_opt = get_parameter ("form_user_opt", "");
		$plugin_pass_opt = get_parameter ("form_pass_opt", "");
		$plugin_plugin_type = get_parameter ("form_plugin_type", "0");
		
		$values = array(
			'name' => $plugin_name,  
			'description' => $plugin_description, 
			'max_timeout' => $plugin_max_timeout, 
			'execute' => $plugin_execute, 
			'net_dst_opt' => $plugin_net_dst_opt, 
			'net_port_opt' => $plugin_net_port_opt, 
			'user_opt' => $plugin_user_opt, 
			'plugin_type' => $plugin_plugin_type,
			'pass_opt' => $plugin_pass_opt); 
		
		$result = false;
		if ($values['name'] != '' && $values['execute'] != '')
			$result = db_process_sql_update('tplugin', $values, array('id' => $plugin_id));
		
		if (! $result) {
			echo "<h3 class='error'>".__('Problem updating plugin')."</h3>";
		}
		else {
			echo "<h3 class='suc'>".__('Plugin updated successfully')."</h3>";
		}
	}
	
	// Create plugin
	if (isset($_GET["create_plugin"])) {
		$plugin_name = get_parameter ("form_name", "");
		$plugin_description = get_parameter ("form_description", "");
		$plugin_max_timeout = get_parameter ("form_max_timeout", "");
		$plugin_execute = get_parameter ("form_execute", "");
		$plugin_net_dst_opt = get_parameter ("form_net_dst_opt", "");
		$plugin_net_port_opt = get_parameter ("form_net_port_opt", "");
		$plugin_user_opt = get_parameter ("form_user_opt", "");
		$plugin_pass_opt = get_parameter ("form_pass_opt", "");
		$plugin_plugin_type = get_parameter ("form_plugin_type", "0");
		
		$values = array(
			'name' => $plugin_name,
			'description' => $plugin_description,
			'max_timeout' => $plugin_max_timeout,
			'execute' => $plugin_execute,
			'net_dst_opt' => $plugin_net_dst_opt,
			'net_port_opt' => $plugin_net_port_opt,
			'user_opt' => $plugin_user_opt,
			'pass_opt' => $plugin_pass_opt,
			'plugin_type' => $plugin_plugin_type);
		
		$result = false;
		if ($values['name'] != '' && $values['execute'] != '')
			$result = db_process_sql_insert('tplugin', $values);
		
		if (! $result) {
			echo "<h3 class='error'>".__('Problem creating plugin')."</h3>";
		}
		else {
			echo "<h3 class='suc'>".__('Plugin created successfully')."</h3>";
		}
	}
	
	if (isset($_GET["kill_plugin"])) { // if delete alert
		$plugin_id = get_parameter ("kill_plugin", 0);
		
		$result = db_process_sql_delete('tplugin', array('id' => $plugin_id));
		
		if (! $result) {
			echo "<h3 class='error'>".__('Problem deleting plugin')."</h3>";
		}
		else {
			echo "<h3 class='suc'>".__('Plugin deleted successfully')."</h3>";
		}
		if ($plugin_id != 0) {
			$result = db_process_sql_delete('tagente_modulo', array('id_plugin' => $plugin_id));
		}
	}
	
	// If not edition or insert, then list available plugins
	$rows = db_get_all_rows_sql('SELECT * FROM tplugin ORDER BY name');
	
	if ($rows !== false) {
		echo '<table width="98%" cellspacing="4" cellpadding="4" class="databox">';
		echo "<th>" . __('Name') . "</th>";
		echo "<th>" . __('Type') . "</th>";
		echo "<th>" . __('Command') . "</th>";
		echo "<th style='width:50px;'>" . '<span title="Operations">' . __('Op.') . '</span>' . "</th>";
		$color = 0;
		
		foreach ($rows as $row) {
			if ($color == 1) {
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			echo "<td class=$tdcolor>";
			echo "<b><a href='index.php?sec=gservers&sec2=godmode/servers/plugin&view=".$row["id"]."'>";
			echo $row["name"];
			echo "</a></b></td>";
			echo "<td class=$tdcolor>";
			if ($row["plugin_type"] == 0)
				echo __('Standard');
			else
				echo __('Nagios');
			echo "</td><td class=$tdcolor>";
			echo $row["execute"];
			echo "</td><td class=$tdcolor>";
			echo "<a href='index.php?sec=gservers&sec2=godmode/servers/plugin&view=".$row["id"]."'>" . html_print_image('images/config.png', true, array("title" => __("Edit"))) . "</a>&nbsp;&nbsp;";
			echo "<a href='index.php?sec=gservers&sec2=godmode/servers/plugin&kill_plugin=".$row["id"]."'>" . html_print_image("images/cross.png", true, array("border" => '0')) . "</a>";
			echo "</td></tr>";
		}
		echo "</table>";
	}
	else {
		echo '<div class="nf">'. __('There are no plugins in the system') . '</div>';
		echo "<br>";
	}
	echo "<table width='98%'>";
	echo "<tr><td align=right>";
	echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&create=1'>";
	echo "<input name='crtbutton' type='submit' class='sub next' value='".__('Add')."'>";
	echo "</td></tr></table>";
	
}


?>