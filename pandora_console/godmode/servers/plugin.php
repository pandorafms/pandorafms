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

if (is_ajax ()) {
	$get_plugin_description = get_parameter('get_plugin_description');
	$get_list_modules_and_component_locked_plugin = (bool)
		get_parameter('get_list_modules_and_component_locked_plugin', 0);
	
	if ($get_plugin_description) {
		$id_plugin = get_parameter('id_plugin');
		
		$description = db_get_value_filter('description', 'tplugin', array('id' => $id_plugin));
		$preload = io_safe_output($description);
		$preload = str_replace ("\n", "<br>", $preload);
		
		echo $preload;
		return;
	}
	
	if ($get_list_modules_and_component_locked_plugin) {
		$id_plugin = (int)get_parameter('id_plugin', 0);
		
		$network_components = db_get_all_rows_filter(
			'tnetwork_component',
			array('id_plugin' => $id_plugin));
		if (empty($network_components)) {
			$network_components = array();
		}
		$modules = db_get_all_rows_filter(
			'tagente_modulo',
			array('delete_pending' => 0, 'id_plugin' => $id_plugin));
		if (empty($modules)) {
			$modules = array();
		}
		
		$table = null;
		$table->width = "100%";
		$table->head[0] = __('Network Components');
		$table->data = array();
		foreach ($network_components as $net_comp) {
			$table->data[] = array($net_comp['name']);
		}
		if (!empty($table->data)) {
			html_print_table($table);
			
			echo "<br />";
		}
		
		$table = null;
		$table->width = "100%";
		$table->head[0] = __('Agent');
		$table->head[1] = __('Module');
		foreach ($modules as $mod) {
			$agent_name = '<a href="' .
				$config['homeurl'] .
					"/index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=" . $mod['id_agente']
				. '">' .
				modules_get_agentmodule_agent_name(
					$mod['id_agente_modulo']) .
				'</a>';
			
			
			$table->data[] = array(
				$agent_name,
				$mod['nombre']
				);
		}
		if (!empty($table->data)) {
			html_print_table($table);
		}
		
		return;
	}
}


require_once ($config['homedir'] . "/include/functions_filemanager.php");

check_login ();

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Plugin Management");
	require ("general/noaccess.php");
	return;
}

enterprise_include_once ('meta/include/functions_components_meta.php');

$view = get_parameter ("view", "");
$create = get_parameter ("create", "");
$filemanager = (bool)get_parameter("filemanager", false);
$edit_file = get_parameter("edit_file",false);
$update_file = get_parameter("update_file",false);
$plugin_command = get_parameter('plugin_command', '');
$tab = get_parameter('tab', '');

if ($view != "") {
	$form_id = $view;
	$plugin = db_get_row ("tplugin", "id", $form_id);
	$form_name = $plugin["name"];
	$form_description = $plugin["description"];
	$form_max_timeout = $plugin ["max_timeout"];
	$form_max_retries = $plugin ["max_retries"];
	if (empty($plugin_command))
		$form_execute = $plugin ["execute"];
	else
		$form_execute = $plugin_command;
	$form_plugin_type = $plugin ["plugin_type"];
	$macros = $plugin ["macros"];
	$parameters = $plugin ["parameters"];
}

if ($create != "") {
	$form_id = 0;
	$form_name = "";
	$form_description = "";
	$form_max_timeout = 15;
	$form_max_retries = 1;
	$form_execute = $plugin_command;
	$form_plugin_type = 0;
	$form_parameters = "";
	$macros = "";
	$parameters = "";
}
//END LOAD VALUES

// =====================================================================
// INIT FILEMANAGER
// =====================================================================
if ($filemanager) {
	if($edit_file) {
		$location_file = get_parameter("location_file",'');
		$filename = array_pop(explode("/",$location_file));
		$file = file_get_contents($location_file);
		echo "<h4>" . __("Edit file") ." ".$filename. "</h4>";
		//echo "<a href='index.php?sec=gagente&sec2=enterprise/godmode/agentes/collections&action=file&id=" . $collection['id'] . "&directory=" . $relative_dir . "&hash2=" . $hash2 . "'>" . __('Back to file explorer') . "</a>";
		echo "<form method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&filemanager=1"."&update_file=1'>";
		//html_print_input_hidden('location_file', $locationFile);
		echo "<table style='width: 98%'>";
		echo "<tr>";
		echo "<th>" . __('Edit') . "</th>";
		echo "</tr>";
		echo "<tr>";
		echo "<td>";
		echo "<textarea name='content_file' style='width: 100%; height: 400px;' >";
		echo $file;
		echo "</textarea>";
		echo "</td>";
		echo "</tr>";
		echo "<tr align='right'>";
		echo "<td>";
		html_print_input_hidden('location_file', $location_file);

		echo __('Compatibility mode').":";
		$options = array('unix' => 'Unix', 'windows' => 'Windows');
		html_print_select($options, 'compatibility', $compatibility);
		echo " <input type='submit' name='submit' value='" . __('Update') . "' class='sub upd' />";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "</form>";
	}else {

		if($update_file){
			$location_file = get_parameter("location_file",'');
			$contentFile = io_safe_output(get_parameter('content_file', ''));
			$compatibility = get_parameter('compatibility', 'unix');
			$is_win_compatible = strpos($contentFile, "\r\n");
			// If is win compatible and the compatibility must be unix
			if ($is_win_compatible !== false && $compatibility == 'unix') {
				$contentFile = str_replace("\r\n", "\n", $contentFile);
			}
			// If is unix compatible and the compatibility must be win
			else if ($is_win_compatible === false && $compatibility == 'windows') {
				$contentFile = str_replace("\n", "\r\n", $contentFile);
			}
			$result = file_put_contents($location_file, $contentFile);

		}
		$id_plugin = (int)get_parameter('id_plugin', 0);

		/* Add custom directories here */
		$fallback_directory = "attachment/plugin";

		$directory = (string) get_parameter ('directory', $fallback_directory);
		$directory = str_replace("\\", "/", $directory);

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

		$homedir_filemanager = isset ($config['homedir_filemanager']) ? $config['homedir_filemanager'] : false;
		filemanager_file_explorer($real_directory,
			$directory,
			'index.php?sec=gservers&sec2=godmode/servers/plugin&filemanager=1&id_plugin=' . $id_plugin,
			$fallback_directory,
			true,
			false,
			'index.php?sec=gservers&sec2=godmode/servers/plugin' . $chunck_url . '&plugin_command=[FILE_FULLPATH]&id_plugin=' . $id_plugin,
			true,
			0775,
			$homedir_filemanager);
	}
	return;
}

// =====================================================================
// END FILEMANAGER
// =====================================================================


// =====================================================================
// SHOW THE FORM
// =====================================================================

$sec = 'gservers';

if (($create != "") OR ($view != "")) {
	enterprise_hook('open_meta_frame');
	
	if (defined('METACONSOLE')) {
		components_meta_print_header();
		$sec = 'advanced';
	}
	else {
		if ($create != "")
			ui_print_page_header(__('Plugin creation'),
				"images/gm_servers.png", false, "plugin_definition", true);
		else {
			ui_print_page_header(__('Plugin update'),
				"images/gm_servers.png", false, "plugin_definition", true);
		}
	}
	
	
	if ($create == "") {
		$plugin_id = get_parameter ("view", "");
		echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&tab=$tab&update_plugin=$plugin_id&pure=" . $config['pure'] . "'>";
	}
	else {
		echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&tab=$tab&create_plugin=1&pure=" . $config['pure'] . "'>";
	}
	
	$table = new stdClass();
	$table->width = '100%';
	$table->id = 'table-form';
	$table->class = 'databox filters';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->style[2] = 'font-weight: bold';
	$table->data = array ();
	
	$data = array();
	$data[0] = __('Name');
	$data[1] = '<input type="text" name="form_name" size=100 value="'.$form_name.'">';
	$table->colspan['plugin_name'][1] = 3;
	$table->data['plugin_name'] = $data;
	
	$data = array();
	$data[0] = __('Plugin type');
	$fields[0]= __("Standard");
	$fields[1]= __("Nagios");
	$data[1] = html_print_select ($fields, "form_plugin_type", $form_plugin_type, '', '', 0, true);
	$table->data['plugin_type'] = $data;
	$table->colspan['plugin_type'][1] = 3;
	
	$data[0] = __('Max. timeout').ui_print_help_tip (__('This value only will be applied if is minor than the server general configuration plugin timeout').'. <br><br>'.__('If you set a 0 seconds timeout, the server plugin timeout will be used'), true);
	$data[1] = html_print_extended_select_for_time ('form_max_timeout', $form_max_timeout, '', '', '0', false, true);

	$table->data['plugin_timeout'] = $data;
	
	$data = array();
	$data[0] = __('Description');
	$data[1] = '<textarea name="form_description" cols="50" rows="4">'.$form_description.'</textarea>';
	$table->colspan['plugin_desc'][1] = 3;
	$table->data['plugin_desc'] = $data;
	
	//if (!defined("METACONSOLE")) {
		//echo '<br>';
		//echo '<table class="databox" style="margin: 0 auto; width: 100%;"><tr><td>';
	//}
	
	$table->width = '100%';
	$table->class = 'databox filters';
	
	if (defined("METACONSOLE")) {
		$table->head[0] = __('General');
		$table->head_colspan[0] = 4;
		$table->headstyle[0] = 'text-align: center';
		html_print_table($table);
	}
	else {
		echo '<fieldset><legend>' . __('General') . '</legend>';
		html_print_table($table);
		echo '</fieldset>';
	}
	
	$table->data = array();
	
	$plugin_id = get_parameter ("view", 0);
	
	$locked = true;
	
	// If we have plugin id (update mode) and this plugin used by any module or component
	// The command configuration will be locked
	if ($plugin_id > 0) {
		$modules_using_plugin = db_get_value_filter('count(*)','tagente_modulo', array('delete_pending' => 0, 'id_plugin' => $plugin_id));
		$components_using_plugin = db_get_value_filter('count(*)','tnetwork_component', array('id_plugin' => $plugin_id));
		if(($components_using_plugin + $modules_using_plugin) == 0) {
			$locked = false;
		}
	}
	else {
		$locked = false;
	}
	
	$disabled = '';
	if ($locked) {
		$disabled = 'readonly="readonly"';
	}
	
	$data = array();
	$data[0] = __('Plugin command');
	$data[1] = '<input type="text" name="form_execute" id="form_execute" class="command_component command_advanced_conf" size=100 value="'.$form_execute.'" '.$disabled.'>';
	if ($locked) {
		$data[1] .= html_print_image('images/lock.png', true, array('class' => 'command_advanced_conf lock'));
	}
	$data[1] .= ' <a href="index.php?sec=gservers&sec2=godmode/servers/plugin&filemanager=1&id_plugin=' . $form_id . '" style="vertical-align: bottom;">';
	$data[1] .= html_print_image('images/file.png', true);
	$data[1] .= '</a>';
	$table->data['plugin_command'] = $data;
	
	$data = array();
	$data[0] = __('Plug-in parameters').ui_print_help_icon ('plugin_parameters', true);
	$data[1] = '<input type="text" name="form_parameters" id="form_parameters" class="command_component command_advanced_conf" size=100 value="'.$parameters.'" '.$disabled.'>';
	if ($locked) {
		$data[1] .= html_print_image('images/lock.png', true, array('class' => 'command_advanced_conf lock'));
	}
	$table->data['plugin_parameters'] = $data;
	
	$data = array();
	$data[0] = __('Command preview');
	$data[1] = '<div id="command_preview" style="font-style:italic"></div>';
	$table->data['plugin_preview'] = $data;
	
	$table->width = '100%';
	$table->class = 'databox filters';
	if (defined("METACONSOLE")) {
		$table->head[0] = __('Command');
		$table->head_colspan[0] = 4;
		$table->headstyle[0] = 'text-align: center';
		html_print_table($table);
	}
	else {
		echo '<fieldset><legend>' . __('Command') . '</legend>';
		html_print_table($table);
		echo '</fieldset>';
	}
	
	$data = array();
	
	$table->data = array ();
	
	$macros = json_decode($macros,true);
	
	// The next row number is plugin_9
	$next_name_number = 9;
	$i = 1;
	while (1) {
		// Always print at least one macro
		if ((!isset($macros[$i]) || $macros[$i]['desc'] == '') && $i > 1) {
			break;
		}
		$macro_desc_name = 'field'.$i.'_desc';
		$macro_desc_value = '';
		$macro_help_name = 'field'.$i.'_help';
		$macro_help_value = '';
		$macro_value_name = 'field'.$i.'_value';
		$macro_value_value = '';
		$macro_name_name = 'field'.$i.'_macro';
		$macro_name = '_field'.$i.'_';
		$macro_hide_value_name = 'field'.$i.'_hide';
		$macro_hide_value_value = 0;
		
		if (isset($macros[$i]['desc'])) {
			$macro_desc_value = $macros[$i]['desc'];
		}
		
		if (isset($macros[$i]['help'])) {
			$macro_help_value = $macros[$i]['help'];
		}
		
		if (isset($macros[$i]['value'])) {
			$macro_value_value = $macros[$i]['value'];
		}
		
		if (isset($macros[$i]['hide'])) {
			$macro_hide_value_value = $macros[$i]['hide'];

			// Decrypt hidden macros.
			$macro_value_value = io_output_password($macro_value_value);
		}
		
		$datam = array ();
		$datam[0] = __('Description')."<span style='font-weight: normal'> ($macro_name)</span>";
		$datam[0] .= html_print_input_hidden($macro_name_name, $macro_name, true);
		$datam[1] = html_print_input_text_extended ($macro_desc_name, $macro_desc_value, 'text-'.$macro_desc_name, '', 30, 255, $locked, '', "class='command_macro'", true);
		if ($locked) {
			$datam[1] .= html_print_image('images/lock.png', true, array('class' => 'command_macro lock'));
		}
		
		$datam[2] = __('Default value')."<span style='font-weight: normal'> ($macro_name)</span>";
		$datam[3] = html_print_input_text_extended ($macro_value_name, $macro_value_value, 'text-'.$macro_value_name, '', 30, 255, $locked, '', "class='command_component command_macro'", true);
		if ($locked) {
			$datam[3] .= html_print_image('images/lock.png', true, array('class' => 'command_macro lock'));
		}
		
		$table->data['plugin_'.$next_name_number] = $datam;
		
		$next_name_number++;
		
		$table->colspan['plugin_'.$next_name_number][1] = 3;
		
		$datam = array ();
		$datam[0] = __('Hide value') . ui_print_help_tip(__('This field will show up as dots like a password'), true);
		$datam[1] = html_print_checkbox_extended ($macro_hide_value_name, 1, $macro_hide_value_value, 0, '', array('class' => 'command_macro'), true, 'checkbox-'.$macro_hide_value_name);

		$table->data['plugin_'.$next_name_number] = $datam;
		$next_name_number++;
		
		$table->colspan['plugin_'.$next_name_number][1] = 3;

		$datam = array ();
		$datam[0] = __('Help')."<span style='font-weight: normal'> ($macro_name)</span><br><br><br>";
		$tadisabled = $locked === true ? ' disabled' : '';
		$datam[1] = html_print_textarea ($macro_help_name, 6, 100, $macro_help_value, 'class="command_macro" style="width: 97%;"' . $tadisabled, true);
		
		if ($locked) {
			$datam[1] .= html_print_image('images/lock.png', true, array('class' => 'command_macro lock'));
		}
		$datam[1] .= "<br><br><br>";
		
		$table->data['plugin_'.$next_name_number] = $datam;
		$next_name_number++;
		$i++;
	}
	
	// Add/Delete buttons
	$datam = array ();
	$datam[0] = '<a id="add_macro_btn" href="javascript:;">' .
					'<span style="font-weight: bold;">' .
						__('Add macro') .
					'</span>' . '&nbsp;' .
					html_print_image('images/add.png',true) .
				'</a>';
	$datam[0] .= '<div id="next_macro" style="display:none">'.$i.'</div>';
	$datam[0] .= '<div id="next_row" style="display:none">'.$next_name_number.'</div>';
	
	if (!$locked) {
		$delete_macro_style = '';
		if ($i <= 2) {
			$delete_macro_style = 'display:none;';
		}
		$datam[2] = '<div id="delete_macro_button" style="'.$delete_macro_style.'">' .
						'<a href="javascript:;">' .
							'<span style="font-weight: bold;">' .
								__('Delete macro') .
							'</span>' . '&nbsp;' .
							html_print_image('images/delete.png',true) .
						'</a>' .
					'</div>';
		
		$table->colspan['plugin_action'][0] = 2;
		$table->colspan['plugin_action'][2] = 2;
	}
	else {
		$table->colspan['plugin_action'][0] = 4;
	}
	
	$table->rowstyle['plugin_action'] = 'text-align:center';
	$table->data['plugin_action'] = $datam;
	
	
	if (defined("METACONSOLE")) {
		$table->head[0] = __('Parameters macros');
		$table->head_colspan[0] = 4;
		$table->headstyle[0] = 'text-align: center';
		html_print_table($table);
	}
	else {
		echo '<fieldset>' .
			'<legend>' . __('Parameters macros') .ui_print_help_icon ('macros', true) . '</legend>';
		html_print_table($table);
		echo '</fieldset>';
	}
	
	echo '<table width="100%">';
	
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
	
	if (defined("METACONSOLE"))
		echo '</td></tr>';
	
	enterprise_hook('close_meta_frame');
}
else {
	
	enterprise_hook('open_meta_frame');
	
	if(defined('METACONSOLE')) {
		components_meta_print_header();
		$sec = 'advanced';
	}
	else {
		ui_print_page_header (__('Plugins registered in Pandora FMS'), "images/gm_servers.png", false, "", true);

		$is_windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
		if ($is_windows) {
			echo '<div class="notify">';
			echo __("You need to create your own plugins with Windows compatibility");
			echo '</div>';
		}
	}

	
	// Update plugin
	if (isset($_GET["update_plugin"])) { // if modified any parameter
		$plugin_id = get_parameter ("update_plugin", 0);
		$plugin_name = get_parameter ("form_name", "");
		$plugin_description = get_parameter ("form_description", "");
		$plugin_max_timeout = get_parameter ("form_max_timeout", "");
		$plugin_execute = get_parameter ("form_execute", "");
		$plugin_plugin_type = get_parameter ("form_plugin_type", "0");
		$parameters = get_parameter ("form_parameters", "");
		
		// Get macros
		$i = 1;
		$macros = array();
		while (1) {
			$macro = (string)get_parameter ('field'.$i.'_macro');
			if($macro == '') {
				break;
			}
			
			$desc = (string)get_parameter ('field'.$i.'_desc');
			$help = (string)get_parameter ('field'.$i.'_help');
			$value = (string)get_parameter ('field'.$i.'_value');
			$hide = get_parameter ('field'.$i.'_hide');
			
			$macros[$i]['macro'] = $macro;
			$macros[$i]['desc'] = $desc;
			$macros[$i]['help'] = $help;
			if ($hide == 1) {
				$macros[$i]['value'] = io_input_password($value);
			} else {
				$macros[$i]['value'] = $value;
			}
			$macros[$i]['hide'] = $hide;

			$i++;
		}
		
		$macros = io_json_mb_encode($macros);
		
		$values = array(
			'name' => $plugin_name,  
			'description' => $plugin_description, 
			'max_timeout' => $plugin_max_timeout, 
			'execute' => $plugin_execute, 
			'plugin_type' => $plugin_plugin_type,
			'parameters' => $parameters,
			'macros' => $macros);
		
		$result = false;
		if ($values['name'] != '' && $values['execute'] != '')
			$result = db_process_sql_update('tplugin', $values,
				array('id' => $plugin_id));
		
		if (! $result) {
			ui_print_error_message(__('Problem updating plugin'));
		}
		else {
			ui_print_success_message(__('Plugin updated successfully'));
		}
	}
	
	// Create plugin
	if (isset($_GET["create_plugin"])) {
		$plugin_name = get_parameter ("form_name", "");
		$plugin_description = get_parameter ("form_description", "");
		$plugin_max_timeout = get_parameter ("form_max_timeout", "");
		$plugin_execute = get_parameter ("form_execute", "");
		$plugin_plugin_type = get_parameter ("form_plugin_type", "0");
		$plugin_parameters = get_parameter ("form_parameters", "");
		
		// Get macros
		$i = 1;
		$macros = array();
		while (1) {
			$macro = (string)get_parameter ('field'.$i.'_macro');
			if($macro == '') {
				break;
			}
			
			$desc = (string)get_parameter ('field'.$i.'_desc');
			$help = (string)get_parameter ('field'.$i.'_help');
			$value = (string)get_parameter ('field'.$i.'_value');
			$hide = get_parameter ('field'.$i.'_hide');
			
			$macros[$i]['macro'] = $macro;
			$macros[$i]['desc'] = $desc;
			$macros[$i]['help'] = $help;
			if ($hide == 1) {
				$macros[$i]['value'] = io_input_password($value);
			} else {
				$macros[$i]['value'] = $value;
			}
			$macros[$i]['hide'] = $hide;
			$i++;
		}
		
		$macros = io_json_mb_encode($macros);
		
		$values = array(
			'name' => $plugin_name,
			'description' => $plugin_description,
			'max_timeout' => $plugin_max_timeout,
			'execute' => $plugin_execute,
			'plugin_type' => $plugin_plugin_type,
			'parameters' => $plugin_parameters,
			'macros' => $macros);
		
		$result = false;
		if ($values['name'] != '' && $values['execute'] != '')
			$result = db_process_sql_insert('tplugin', $values);
		
		if (! $result) {
			ui_print_error_message(__('Problem creating plugin'));
		}
		else {
			ui_print_success_message(__('Plugin created successfully'));
		}
	}
	
	if (isset($_GET["kill_plugin"])) { // if delete alert
		$plugin_id = get_parameter ("kill_plugin", 0);
		
		$result = db_process_sql_delete('tplugin', array('id' => $plugin_id));
		
		if (! $result) {
			ui_print_error_message(__('Problem deleting plugin'));
		}
		else {
			ui_print_success_message(__('Plugin deleted successfully'));
		}
		if ($plugin_id != 0) {
			// Delete all the modules with this plugin
			$plugin_modules = db_get_all_rows_filter(
				'tagente_modulo', array('id_plugin' => $plugin_id));
			
			if (empty($plugin_modules))
				$plugin_modules = array();
			
			foreach ($plugin_modules as $pm) {
				modules_delete_agent_module ($pm['id_agente_modulo']);
			}
			if (enterprise_installed()) {
				enterprise_include_once('include/functions_policies.php');
				$policies_ids = db_get_all_rows_filter('tpolicy_modules', array('id_plugin' => $plugin_id));
				foreach($policies_ids as $policies_id) {
					policies_change_delete_pending_module ($policies_id['id']);
				}
			}
		}
	}
	
	// If not edition or insert, then list available plugins
	$rows = db_get_all_rows_sql('SELECT * FROM tplugin ORDER BY name');
	
	if ($rows !== false) {
		if(defined('METACONSOLE'))
			echo '<table width="100%" cellspacing="4" cellpadding="4" class="databox">';
		else
			echo '<table width="100%" cellspacing="4" cellpadding="4" class="databox data">';
			
		echo "<th>" . __('Name') . "</th>";
		echo "<th>" . __('Type') . "</th>";
		echo "<th>" . __('Command') . "</th>";
		echo "<th style='width: 90px;'>" .
			'<span title="Operations">' . __('Op.') . '</span>' .
			"</th>";
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
			echo "<b><a href='index.php?sec=$sec&sec2=godmode/servers/plugin&view=".$row["id"]."&tab=plugins&pure=" . $config['pure'] . "'>";
			echo $row["name"];
			echo "</a></b></td>";
			echo "<td class=$tdcolor>";
			if ($row["plugin_type"] == 0)
				echo __('Standard');
			else
				echo __('Nagios');
			echo "</td><td class=$tdcolor>";
			echo $row["execute"];
			echo "</td>";
			echo "<td class='$tdcolor' align='center'>";
			
			//Show it is locket
			$modules_using_plugin = db_get_value_filter(
				'count(*)',
				'tagente_modulo',
				array('delete_pending' => 0, 'id_plugin' => $row["id"]));
			$components_using_plugin = db_get_value_filter(
				'count(*)',
				'tnetwork_component',
				array('id_plugin' => $row["id"]));
			if (($components_using_plugin + $modules_using_plugin) > 0) {
				echo '<a href="javascript: show_locked_dialog(' . $row['id'] . ', \'' . $row["name"] . '\');">';
				html_print_image('images/lock.png');
				echo "</a>";
			}
			echo "<a href='index.php?sec=$sec&sec2=godmode/servers/plugin&tab=$tab&view=".$row["id"]."&tab=plugins&pure=" . $config['pure'] . "'>" . html_print_image('images/config.png', true, array("title" => __("Edit"))) . "</a>&nbsp;&nbsp;";
			echo "<a href='index.php?sec=$sec&sec2=godmode/servers/plugin&tab=$tab&kill_plugin=".$row["id"]."&tab=plugins&pure=" . $config['pure'] . "' onclick='javascript: if (!confirm(\"" . __('All the modules that are using this plugin will be deleted') . '. ' . __('Are you sure?') . "\")) return false;'>" . html_print_image("images/cross.png", true, array("border" => '0')) . "</a>";
			echo "</td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	else {
		ui_print_info_message ( array('no_close'=>true, 'message'=>  __('There are no plugins in the system') ) );
	}
	
	echo "<table width='100%'>";

	echo "<tr><td align=right>";
	echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&tab=$tab&create=1&pure=" . $config['pure'] . "'>";
	echo "<input name='crtbutton' type='submit' class='sub next' value='".__('Add')."'>";
	echo "</td></tr></table>";
	
	// The '%s' will be replaced in the javascript code of the function 'show_locked_dialog'
	echo "<div id='dialog_locked' title='" .
		__('List of modules and components created by "%s" ') .
		"' style='display: none; text-align: left;'>";
	echo "</div>";
	
	enterprise_hook('close_meta_frame');
}

ui_require_javascript_file('pandora_modules');

?>

<script type="text/javascript">
	
	var locked = <?php echo (int)json_encode((int)$locked); ?>;
	
	function update_preview() {
		var command = $('#form_execute').val();
		var parameters = $('#form_parameters').val();
		
		var i = 1;
		
		while (1) {
			if ($('#text-field' + i + '_value').val() == undefined) {
				break;
			}
			
			if ($('#text-field'+i+'_value').val() != '') {
				parameters = parameters
					.replace('_field' + i + '_',
						$('#text-field' + i + '_value').val());
			}
			
			i++;
		}
		
		$('#command_preview').html(command+' '+parameters);
	}
	
	function show_locked_dialog(id_plugin, plugin_name) {
		var parameters = {};
		parameters['page'] = "godmode/servers/plugin";
		parameters["get_list_modules_and_component_locked_plugin"] = 1;
		parameters["id_plugin"] = id_plugin;
		
		$.ajax({
			type: "POST",
			url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
			data: parameters,
			dataType: "html",
			success: function(data) {
				var title = $("#dialog_locked").prop('title').replace(/%s/, plugin_name);
				
				$("#dialog_locked")
					.prop('title', title)
					.html(data)
					.dialog ({
						resizable: true,
						draggable: true,
						modal: true,
						overlay: {
							opacity: 0.5,
							background: "black"
						},
						width: 650,
						height: 500
					})
					.show ();
			}
		});
	}
	
	
	$(document).ready(function() {
		// Add macro
		var add_macro_click_event = function (event) {
			new_macro('table-form-plugin_', function () {
				// Remove the locked images and enable the inputs
				if (arguments.length > 0) {
					var rows = _.toArray(arguments);
					_.each(rows, function(row, index) {
						row.find('input.command_macro, textarea.command_macro')
							.prop('readonly', false)
							.prop('disabled', false)
							.siblings('img.command_macro.lock')
								.remove();
					});
				}
			});
			update_preview();
		}
		$('a#add_macro_btn').click(add_macro_click_event);
		
		// Delete macro
		var delete_macro_click_event = function (event) {
			delete_macro('table-form-plugin_');
			update_preview();
		}
		$('div#delete_macro_button>a').click(delete_macro_click_event);
		
		update_preview();
		
		$('.command_component').keyup(function() {
			update_preview();
		});
	});
	
	
	var add_macro_click_locked_event = function (event) {
		var message = '<?php echo __("Some modules or components are using the plugin"); ?>.'
					+ '\n' + '<?php echo __("The modules or components should be updated manually or using the bulk operations for plugins after this change"); ?>.'
					+ '\n'
					+ '\n' + '<?php echo __("Are you sure you want to perform this action?"); ?>';
		
		if (!confirm(message)) {
			event.stopImmediatePropagation();
			event.preventDefault();
		}
	}
	
	var command_click_locked_event = function (event) {
		var $element = $(this);
		
		if (!$element.is('input') || ($element.is('input') && ($element.prop('readonly') || $element.prop('disabled')))) {
			if ($element.prop('id') === 'form_parameters') {
				var message = '<?php echo __("Some modules or components are using the plugin"); ?>'
					+ '.\n' + '<?php echo __("Are you sure you want to unlock this item?"); ?>';
				
				if (confirm(message)) {
					if ($element.is('input')) {
						$element
							.prop('readonly', false)
							.prop('disabled', false)
							.siblings('img.command_advanced_conf.lock')
								.remove();
					}
					else {
						$element
							.siblings('input.command_advanced_conf')
								.prop('readonly', false)
								.prop('disabled', false)
						$element.remove();
					}
				}
			}
			else {
				alert('<?php echo __("The plugin command cannot be updated because some modules or components are using the plugin."); ?>');
			}
		}
	}
	
	var macros_click_locked_event = function (event) {
		alert('<?php echo __("The plugin macros cannot be updated because some modules or components are using the plugin"); ?>');
	}
	
	if (locked) {
		$('a#add_macro_btn').click(add_macro_click_locked_event);
		$('.command_advanced_conf').click(command_click_locked_event);
		$('.command_macro').click(macros_click_locked_event);
	}
	
</script>
