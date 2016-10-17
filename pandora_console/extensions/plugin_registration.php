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

function pluginreg_extension_main () {
	global $config;
	
	if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
		db_pandora_audit("ACL Violation", "Trying to access Setup Management");
		require ("general/noaccess.php");
		return;
	}
	ui_require_css_file ('firts_task');
	ui_print_page_header (__('Plugin registration'), "images/extensions.png", false, "", true, "" );
	
	echo '<div class="new_task">
			<div class="image_task">';
				echo html_print_image("images/firts_task/icono_grande_import.png", true, array("title" => __("Plugin Registration") ));
			echo '</div>';
				echo '<div class="text_task">';
					echo '<h3>' . __("Plugin registration") . '</h3>';
					echo '<p id="description_task">' . 
					__("This extension makes registration of server plugins more easy. 
						Here you can upload a server plugin in Pandora FMS 3.x zipped format (.pspz). 
						Please refer to documentation on how to obtain and use Pandora FMS Server Plugins.
						<br><br>You can get more plugins in our <a href='http://pandorafms.com/Library/Library/'>Public Resource Library</a> ") . '</p>';
						// Upload form
					echo "<form name='submit_plugin' method='post' enctype='multipart/form-data'>";
					echo '<table class="" id="table1" width="100%" border="0" cellpadding="4" cellspacing="4">';
					echo "<tr><td class='datos'><input type='file' name='plugin_upload' />";
					echo "<td class='datos'><input type='submit' class='sub next' value='".__('Upload')."' />";
					echo "</form></table>";
				echo '</div>';
	echo '</div>';

	
	$zip = null;
	$upload = false;
	if (isset($_FILES['plugin_upload'])) {
		$config["plugin_store"] = $config["attachment_store"] . "/plugin";
		
		$name_file = $_FILES['plugin_upload']['name'];
		
		$zip = zip_open($_FILES['plugin_upload']['tmp_name']);
		$upload = true;
	}
	
	if ($zip) {
		while ($zip_entry = zip_read($zip)) {
			if (zip_entry_open($zip, $zip_entry, "r")) {
				if (zip_entry_name($zip_entry) == "plugin_definition.ini") {
					$basepath = $config["attachment_store"];
				}
				else {
					$basepath = $config["plugin_store"];
				}
				$filename = $basepath . "/". zip_entry_name($zip_entry);
				$fp = fopen($filename, 'w');
				$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
					fwrite($fp, $buf);
					fclose($fp);
					chmod ($filename, 0755);
				zip_entry_close($zip_entry);
			}
		}
		zip_close($zip);
	}
	
	if ($upload) {
		// Parse with sections
		if (! $ini_array = parse_ini_file($config["attachment_store"] . "/plugin_definition.ini", true)) {
			echo "<h2 class=error>".__("Cannot load INI file")."</h2>";
		}
		else {
			
			$version = preg_replace("/.*[.]/", "", $name_file);
			
			$exec_path = $config["plugin_store"] . "/" . $ini_array["plugin_definition"]["filename"];
			
			$file_exec_path = $exec_path;
			
			if (isset($ini_array["plugin_definition"]["execution_command"])
				&& ($ini_array["plugin_definition"]["execution_command"] != "")) {
				
				$exec_path = $ini_array["plugin_definition"]["execution_command"] . " " . $config["plugin_store"] . "/" . $ini_array["plugin_definition"]["filename"];
			}
			
			if (isset($ini_array["plugin_definition"]["execution_postcommand"])
				&& ($ini_array["plugin_definition"]["execution_postcommand"] != "")) {
				
				$exec_path = $exec_path . " " .$ini_array["plugin_definition"]["execution_postcommand"];
			}
			
			if (!file_exists($file_exec_path)) {
				echo "<h2 class=error>".__("Plugin exec not found. Aborting!")."</h2>";
				unlink ($config["attachment_store"] . "/plugin_definition.ini");
			}
			else {
				// Verify if a plugin with the same name is already registered
				
				$sql0 = "SELECT COUNT(*)
					FROM tplugin
					WHERE name = '" . io_safe_input ($ini_array["plugin_definition"]["name"]) . "'";
				$result = db_get_sql ($sql0);
				
				
				if ($result> 0) {
					echo "<h2 class=error>".__("Plugin already registered. Aborting!")."</h2>";
					unlink ($config["attachment_store"] . "/plugin_definition.ini");
				}
				else {
					
					$values = array(
						'name' => io_safe_input ($ini_array["plugin_definition"]["name"]),
						'description' => io_safe_input ($ini_array["plugin_definition"]["description"]),
						'max_timeout' => $ini_array["plugin_definition"]["timeout"],
						'execute' => io_safe_input ($exec_path),
						'net_dst_opt' => $ini_array["plugin_definition"]["ip_opt"],
						'net_port_opt' => $ini_array["plugin_definition"]["port_opt"],
						'user_opt' => $ini_array["plugin_definition"]["user_opt"],
						'pass_opt' => $ini_array["plugin_definition"]["pass_opt"],
						'parameters' => $ini_array["plugin_definition"]["parameters"],
						'plugin_type' => $ini_array["plugin_definition"]["plugin_type"]);
					
					
					switch ($version) {
						case 'pspz':
							// Fixed the static parameters
							// for
							// the dinamic parameters of pandoras 5
							
							$total_macros = 0;
							$macros = array();
							
							if (!isset($values['parameters']))
								$values['parameters'] = "";
							
							if ($values['net_dst_opt'] != "") {
								$total_macros++;
								
								$macro = array();
								$macro['macro'] = '_field' . $total_macros . '_';
								$macro['desc'] = 'Target IP from net';
								$macro['help'] = '';
								$macro['value'] = '';
								
								$values['parameters'] .=
									$values['net_dst_opt'] . ' _field' . $total_macros . '_ ';
								
								$macros[(string)$total_macros] = $macro;
							}
							
							if ($values['ip_opt'] != "") {
								$total_macros++;
								
								$macro = array();
								$macro['macro'] = '_field' . $total_macros . '_';
								$macro['desc'] = 'Target IP';
								$macro['help'] = '';
								$macro['value'] = '';
								
								$values['parameters'] .=
									$values['ip_opt'] . ' _field' . $total_macros . '_ ';
								
								$macros[(string)$total_macros] = $macro;
							}
							
							if ($values['net_port_opt'] != "") {
								$total_macros++;
								
								$macro = array();
								$macro['macro'] = '_field' . $total_macros . '_';
								$macro['desc'] = 'Port from net';
								$macro['help'] = '';
								$macro['value'] = '';
								
								$values['parameters'] .=
									$values['net_port_opt'] . ' _field' . $total_macros . '_ ';
								
								$macros[(string)$total_macros] = $macro;
							}
							
							if ($values['port_opt'] != "") {
								$total_macros++;
								
								$macro = array();
								$macro['macro'] = '_field' . $total_macros . '_';
								$macro['desc'] = 'Port';
								$macro['help'] = '';
								$macro['value'] = '';
								
								$values['parameters'] .=
									$values['port_opt'] . ' _field' . $total_macros . '_ ';
								
								$macros[(string)$total_macros] = $macro;
							}
							
							if ($values['user_opt'] != "") {
								$total_macros++;
								
								$macro = array();
								$macro['macro'] = '_field' . $total_macros . '_';
								$macro['desc'] = 'Username';
								$macro['help'] = '';
								$macro['value'] = '';
								
								$values['parameters'] .=
									$values['user_opt'] . ' _field' . $total_macros . '_ ';
								
								$macros[(string)$total_macros] = $macro;
							}
							
							if ($values['pass_opt'] != "") {
								$total_macros++;
								
								$macro = array();
								$macro['macro'] = '_field' . $total_macros . '_';
								$macro['desc'] = 'Password';
								$macro['help'] = '';
								$macro['value'] = '';
								
								$values['parameters'] .=
									$values['pass_opt'] . ' _field' . $total_macros . '_ ';
								
								$macros[(string)$total_macros] = $macro;
							}
							
							// A last parameter is defined always to
							// add the old "Plug-in parameters" in the
							// side of the module
							$total_macros++;
							
							$macro = array();
							$macro['macro'] = '_field' . $total_macros . '_';
							$macro['desc'] = 'Plug-in Parameters';
							$macro['help'] = '';
							$macro['value'] = '';
							
							$values['parameters'] .=
								' _field' . $total_macros . '_';
							
							$macros[(string)$total_macros] = $macro;
							
							break;
						case 'pspz2':
							// Fill the macros field.
							$total_macros =
								$ini_array["plugin_definition"]["total_macros_provided"];
							
							$macros = array();
							for ($it_macros = 1; $it_macros <= $total_macros; $it_macros++) {
								$label = "macro_" . $it_macros;
								
								$macro = array();
								
								$macro['macro'] = '_field' . $it_macros . '_';
								$macro['hide'] =
									$ini_array[$label]['hide'];
								$macro['desc'] = io_safe_input(
									$ini_array[$label]['description']);
								$macro['help'] = io_safe_input(
									$ini_array[$label]['help']);
								$macro['value'] = io_safe_input(
									$ini_array[$label]['value']);
								
								$macros[(string)$it_macros] = $macro;
							}
							break;
					}
					
					if (!empty($macros)) {
						$values['macros'] = json_encode($macros);
					}
					
					$create_id = db_process_sql_insert('tplugin', $values);
					
					if (empty($create_id)) {
						ui_print_error_message(
							__('Plug-in Remote Registered unsuccessfull'));
						ui_print_info_message(
							__('Please check the syntax of file "plugin_definition.ini"'));
					}
					else {
						for ($ax = 1; $ax <= $ini_array["plugin_definition"]["total_modules_provided"]; $ax++) {
							$label = "module" . $ax;
							
							$plugin_user = "";
							if (isset($ini_array[$label]["plugin_user"]))
								$plugin_user = $ini_array[$label]["plugin_user"];
							$plugin_pass = "";
							if (isset($ini_array[$label]["plugin_pass"]))
								$plugin_pass = $ini_array[$label]["plugin_pass"];
							$plugin_parameter = "";
							if (isset($ini_array[$label]["plugin_parameter"]))
								$plugin_parameter = $ini_array[$label]["plugin_parameter"];
							$unit = "";
							if (isset($ini_array[$label]["unit"]))
								$unit = $ini_array[$label]["unit"];
							
							$values = array(
								'name' => io_safe_input ($ini_array[$label]["name"]),
								'description' => io_safe_input ($ini_array[$label]["description"]),
								'id_group' => $ini_array[$label]["id_group"],
								'type' => $ini_array[$label]["type"],
								'max' => isset($ini_array[$label]["max"]) ? $ini_array[$label]["max"] : '',
								'min' => isset($ini_array[$label]["min"]) ? $ini_array[$label]["min"] : '',
								'module_interval' => isset($ini_array[$label]["module_interval"]) ? $ini_array[$label]["module_interval"] : '',
								'id_module_group' => $ini_array[$label]["id_module_group"],
								'id_modulo' => $ini_array[$label]["id_modulo"], 
								'plugin_user' => io_safe_input ($plugin_user),
								'plugin_pass' => io_safe_input ($plugin_pass),
								'plugin_parameter' => io_safe_input ($plugin_parameter),
								'unit' => io_safe_input ($unit),
								'max_timeout' => isset($ini_array[$label]["max_timeout"]) ? $ini_array[$label]["max_timeout"] : '',
								'history_data' => isset($ini_array[$label]["history_data"]) ? $ini_array[$label]["history_data"] : '',
								'dynamic_interval' => isset($ini_array[$label]["dynamic_interval"]) ? $ini_array[$label]["dynamic_interval"] : '',
								'dynamic_min' => isset($ini_array[$label]["dynamic_min"]) ? $ini_array[$label]["dynamic_min"] : '',
								'dynamic_max' => isset($ini_array[$label]["dynamic_max"]) ? $ini_array[$label]["dynamic_max"] : '',
								'dynamic_two_tailed' => isset($ini_array[$label]["dynamic_two_tailed"]) ? $ini_array[$label]["dynamic_two_tailed"] : '',
								'min_warning' => isset($ini_array[$label]["min_warning"]) ? $ini_array[$label]["min_warning"] : '',
								'max_warning' => isset($ini_array[$label]["max_warning"]) ? $ini_array[$label]["max_warning"] : '',
								'str_warning' => isset($ini_array[$label]["str_warning"]) ? $ini_array[$label]["str_warning"] : '',
								'min_critical' => isset($ini_array[$label]["min_critical"]) ? $ini_array[$label]["min_critical"] : '',
								'max_critical' => isset($ini_array[$label]["max_critical"]) ? $ini_array[$label]["max_critical"] : '',
								'str_critical' => isset($ini_array[$label]["str_critical"]) ? $ini_array[$label]["str_critical"] : '',
								'min_ff_event' => isset($ini_array[$label]["min_ff_event"]) ? $ini_array[$label]["min_ff_event"] : '',
								'tcp_port' => isset($ini_array[$label]["tcp_port"]) ? $ini_array[$label]["tcp_port"] : '',
								'id_plugin' => $create_id);
							
							$macros_component = $macros;
							
							switch ($version) {
								case 'pspz':
									// Fixed the static parameters
									// for
									// the dinamic parameters of pandoras 5
									
									foreach ($macros_component as $key => $macro) {
										if ($macro['desc'] == 'Target IP from net') {
											if (!empty($values['ip_target'])) {
												$macros_component[$key]['value'] =
													io_safe_input($values['ip_target']);
											}
										}
										if ($macro['desc'] == 'Target IP') {
											if (!empty($values['ip_target'])) {
												$macros_component[$key]['value'] =
													io_safe_input($values['ip_target']);
											}
										}
										else if ($macro['desc'] == 'Port from net') {
											if (!empty($values['tcp_port'])) {
												$macros_component[$key]['value'] =
													io_safe_input($values['tcp_port']);
											}
										}
										else if ($macro['desc'] == 'Port') {
											if (!empty($values['tcp_port'])) {
												$macros_component[$key]['value'] =
													io_safe_input($values['tcp_port']);
											}
										}
										else if ($macro['desc'] == 'Username') {
											if (!empty($values['plugin_user'])) {
												$macros_component[$key]['value'] =
													io_safe_input($values['plugin_user']);
											}
										}
										else if ($macro['desc'] == 'Password') {
											if (!empty($values['plugin_pass'])) {
												$macros_component[$key]['value'] =
													io_safe_input($values['plugin_pass']);
											}
										}
										else if ($macro['desc'] == 'Plug-in Parameters') {
											if (!empty($values['plugin_parameter'])) {
												$macros_component[$key]['value'] =
													io_safe_input($values['plugin_parameter']);
											}
										}
									}
									break;
								case 'pspz2':
									if ($total_macros > 0) {
										for ($it_macros = 1; $it_macros <= $total_macros; $it_macros++) {
											$macro = "macro_" . $it_macros . "_value";
											
											// Set the value or use the default
											if (isset($ini_array[$label][$macro])) {
												$macros_component[(string)$it_macros]['value'] =
													io_safe_input($ini_array[$label][$macro]);
											}
										}
									}
									break;
							}
							
							if (!empty($macros_component)) {
								$values['macros'] = json_encode($macros_component);
							}
							
							db_process_sql_insert('tnetwork_component', $values);
							
							echo "<h3 class=suc>" .
								__("Module plugin registered") . " : " . $ini_array[$label]["name"] .
								"</h3>";
						}
						
						echo "<h2 class=suc>" .
								__("Plugin") . " " . $ini_array["plugin_definition"]["name"] . " " . __("Registered successfully") .
							"</h2>";
					}
					unlink ($config["attachment_store"] . "/plugin_definition.ini");
				}
			}
		}
	}
}

extensions_add_godmode_menu_option (__('Register plugin'), 'PM','gservers', null, "v1r1");
extensions_add_godmode_function('pluginreg_extension_main');

?>
