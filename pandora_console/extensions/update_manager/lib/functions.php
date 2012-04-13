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

function update_pandora_check_installation() {
	global $config;
	
	$conf_update_pandora = db_get_row('tconfig', 'token', 'update_pandora_conf_url');
	
	$dir = $config['attachment_store'] .  '/update_pandora/';
	
	if (empty($conf_update_pandora) || !is_dir($dir))
		return false;
	else
		return true;
}

function update_pandora_get_conf() {
	global $config;
	
	$conf = array();
	$row = db_get_row('tconfig', 'token', 'update_pandora_conf_url');
	$conf['url'] = $row['value'];
	$row = db_get_row('tconfig', 'token', 'update_pandora_conf_last_installed');
	$conf['last_installed'] = $row['value'];
	$row = db_get_row('tconfig', 'token', 'update_pandora_conf_last_contact');
	$conf['last_contact'] = $row['value'];
	$row = db_get_row('tconfig', 'token', 'update_pandora_conf_download_mode');
	$conf['download_mode'] = $row['value'];
	
	$conf['dir'] = $config['attachment_store'] .  '/update_pandora/';
	
	return $conf;
}

function update_pandora_installation() {
	global $config;
	
	$row = db_get_row_filter('tconfig', array('token' => 'update_pandora_conf_url'));
	
	if (empty($row)) {
		//The url of update manager.
		$conf_update_pandora = array('url' => 'http://192.168.70.213/pandora.tar.gz',
			'last_installed' => '',
			'last_contact' => '',
			'download_mode' => 'curl');
		
		$values = array('token' => 'update_pandora_conf_url',
			'value' => $conf_update_pandora['url']);
		$return = db_process_sql_insert('tconfig', $values);
		$values = array('token' => 'update_pandora_conf_last_installed',
			'value' => $conf_update_pandora['last_installed']);
		$return = db_process_sql_insert('tconfig', $values);
		$values = array('token' => 'update_pandora_conf_last_contact',
			'value' => $conf_update_pandora['last_contact']);
		$return = db_process_sql_insert('tconfig', $values);
		$values = array('token' => 'update_pandora_conf_download_mode',
			'value' => $conf_update_pandora['download_mode']);
		$return = db_process_sql_insert('tconfig', $values);
		
		ui_print_result_message($return, __('Succesful store conf data in DB.'),
			__('Unsuccesful store conf data in DB.'));
	}
	else {
		ui_print_message(__('Conf data have been in the DB.'));
	}
	
	$dir = $config['attachment_store'] .  '/update_pandora/';
	if (!is_dir($dir)) {
		$result = mkdir($dir);
		
		ui_print_result_message($result, __('Succesful create a dir to save package in Pandora Console'),
			__('Unsuccesful create a dir to save package in Pandora Console'));
	}
	else {
		ui_print_message(__('The directory for save package have been in Pandora Console.'));
	}
}

function update_pandora_update_conf() {
	global $config;
	global $conf_update_pandora;
	
	$values = array('value' => $conf_update_pandora['last_installed']);
	$return = db_process_sql_update('tconfig', $values,
		array('token' => 'update_pandora_conf_last_installed'));
	$values = array('value' => $conf_update_pandora['last_contact']);
	$return = db_process_sql_update('tconfig', $values,
		array('token' => 'update_pandora_conf_last_contact'));
	$values = array('value' => $conf_update_pandora['download_mode']);
	$return = db_process_sql_update('tconfig', $values,
		array('token' => 'update_pandora_conf_download_mode'));
	
	return $return;
}

function update_pandora_get_list_downloaded_packages($mode = 'operation') {
	global $config;
	global $conf_update_pandora;
	
	$dir = dir($conf_update_pandora['dir']);
	
	$packages = array();
	while (false !== ($entry = $dir->read())) {
		if (is_file($conf_update_pandora['dir'] . $entry)
			&& is_readable($conf_update_pandora['dir'] . $entry)) {
			if (strstr($entry, '.tar.gz') !== false) {
				
				if ($mode == 'operation') {
					$packages[] = $entry;
				}
				else {
					$time_file = date($config["date_format"],
						filemtime($conf_update_pandora['dir'] . $entry));
					
					if ($conf_update_pandora['last_installed'] == $entry) {
						$packages[] = array('name' => $entry,
							'current' => true,
							'time' => $time_file);
					}
					else {
						$packages[] = array('name' => $entry,
							'current' => false,
							'time' => $time_file);
					}
				}
			}
		}
	}
	
	if (empty($packages)) {
		if ($mode == 'operation') {
			$packages[] = array('name' => 
				__('There are not downloaded packages in your Pandora Console.'),
				'time' => '');
		}
		else {
			$packages[] = array('empty' => true, 'name' =>
				__('There are not downloaded packages in your Pandora Console.'),
				'time' => '');
		}
	}
	
	return $packages;
}

function update_pandora_print_javascript() {
	$extension_php_file = 'extensions/update_pandora';
	
	?>
	<script type="text/javascript">
		var last = 0;
		$(document).ready(function() {
			ajax_get_online_package_list();
		});
		
		function ajax_get_online_package_list() {
			var parameters = {};
			parameters['page'] = "<?php echo $extension_php_file;?>";
			parameters['get_packages_online'] = 1;
			parameters['last'] = last;
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: parameters,
				dataType: "json",
				success: function(data) {
					if (data['correct'] == 1) {
						var row = $("<tr class='package'></tr>")
							.append().html('<td>' + data['package'] + '</td>');
						$("tbody", "#online_packages").append($(row));
						
						last = data['last'];
						
						if (data['end'] == 1) {
							$(".spinner_row", "#online_packages").remove();
						}
						else {
							ajax_get_online_package_list();
						}
					}
					else {
						$(".spinner_row", "#online_packages").remove();
					}
				}
			});
		}
	</script>
	<?php
}

function update_pandora_print_javascript_admin() {
	$extension_php_file = 'extensions/update_manager/update_pandora';
	
	?>
	<script type="text/javascript">
		var disabled_download_package = false;
		var last = 0;
		
		$(document).ready(function() {
			ajax_get_online_package_list_admin();
			
			$("#submit-hide_download_dialog").click (function () {
				//Better than fill the downloaded packages
				location.reload();
				//$("#dialog_download" ).dialog('close');
			});
		});
		
		function delete_package(package) {
			url = window.location + "&delete_package=1"
				+ '&package=' + package;
			
			window.location.replace(url);
		}
		
		function ajax_start_install_package(package) {
			$(".package_name").html(package);
			
			$("#dialog_download").dialog({
					resizable: false,
					draggable: false,
					modal: true,
					height: 400,
					width: 650,
					overlay: {
							opacity: 0.5,
							background: "black"
						},
					bgiframe: jQuery.browser.msie
				});
			$(".ui-dialog-titlebar-close").hide();
			$("#dialog_download").show();
			
			$("#title_downloading_update_pandora").hide();
			$("#title_downloaded_update_pandora").show();
			$("#title_installing_update_pandora").show();
			
			install_package(package, package);
		}
		
		function ajax_start_download_package(package) {
			$(".package_name").html(package);
			
			$("#dialog_download").dialog({
					resizable: false,
					draggable: true,
					modal: true,
					height: 400,
					width: 650,
					overlay: {
							opacity: 0.5,
							background: "black"
						},
					bgiframe: jQuery.browser.msie
				});
			$(".ui-dialog-titlebar-close").hide();
			$("#dialog_download").show();
			
			var parameters = {};
			parameters['page'] = "<?php echo $extension_php_file;?>";
			parameters['download_package'] = 1;
			parameters['package'] = package;
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: parameters,
				dataType: "json",
				success: function(data) {
					if (data['correct'] == 1) {
						if (data['mode'] == 'wget') {
							disabled_download_package = true;
							$("#progress_bar_img img").show();
							install_package(package, data['filename']);
						}
					}
				}
			});
			
			check_download_package(package);
		}
		
		function check_download_package(package) {
			var parameters = {};
			parameters['page'] = "<?php echo $extension_php_file;?>";
			parameters['check_download_package'] = 1;
			parameters['package'] = package;
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: parameters,
				dataType: "json",
				success: function(data) {
					if (disabled_download_package)
						return;
					
					if (data['correct'] == 1) {
						if (data['mode'] == 'wget') {
							$("#info_text").show();
							$("#info_text").html(data['info_download']);
							$("#progress_bar_img img").hide();
						}
						else {
							$("#info_text").show();
							$("#info_text").html(data['info_download']);
							
							$("#progress_bar_img img").attr('src', data['progres_bar_src']);
							$("#progress_bar_img img").attr('alt', data['progres_bar_alt']);
							$("#progress_bar_img img").attr('title', data['progres_bar_title']);
							
							if (data['percent'] < 100) {
								check_download_package(package);
							}
							else {
								install_package(package, data['filename']);
							}
						}
					}
					else {
						$("#title_downloading_update_pandora").hide();
						$("#title_error_update_pandora").show();
						$("#progress_bar_img").hide();
						
						$("#info_text").html('');
						
						$("#button_close_download_disabled").hide();
						$("#button_close_download").show();
					}
				}
			});
		}
		
		function install_package(package, filename) {
			var parameters = {};
			parameters['page'] = "<?php echo $extension_php_file;?>";
			parameters['install_package'] = 1;
			parameters['package'] = package;
			parameters['filename'] = filename;
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: parameters,
				dataType: "json",
				success: function(data) {
				}
			});
			
			$("#title_downloading_update_pandora").hide();
			$("#title_downloaded_update_pandora").show();
			$("#title_installing_update_pandora").show();
			
			check_install_package(package, filename);
		}
		
		function check_install_package(package, filename) {
			var parameters = {};
			parameters['page'] = "<?php echo $extension_php_file;?>";
			parameters['check_install_package'] = 1;
			parameters['package'] = package;
			parameters['filename'] = filename;
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: parameters,
				dataType: "json",
				success: function(data) {
					if (data['correct'] == 1) {
						$("#info_text").show();
						$("#info_text").html(data['info']);
						$("#list_files_install").scrollTop(
							$("#list_files_install").attr("scrollHeight"));
						
						$("#progress_bar_img img").attr('src', data['src']);
						$("#progress_bar_img img").attr('alt', data['alt']);
						$("#progress_bar_img img").attr('title', data['title']);
						
						if (data['percent'] < 100) {
							check_install_package(package, filename);
						}
						else {
							$("#title_installing_update_pandora").hide();
							$("#title_installed_update_pandora").show();
							$("#button_close_download_disabled").hide();
							$("#button_close_download").show();
						}
					}
				}
			})
		}
		
		function ajax_get_online_package_list_admin() {
			var buttonUpdateTemplate = '<?php
				html_print_button(__('Update'), 'update', false,
					'ajax_start_download_package(\\\'pandoraFMS\\\');', 'class="sub upd"');
				?>';
			
			var parameters = {};
			parameters['page'] = "<?php echo $extension_php_file;?>";
			parameters['get_packages_online'] = 1;
			parameters['last'] = last;
			
			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: parameters,
				dataType: "json",
				success: function(data) {
					if (data['correct'] == 1) {
						buttonUpdate = buttonUpdateTemplate.replace('pandoraFMS', data['package']);
						
						$("tbody", "#online_packages").append(
							'<tr class="package_' + data['package'] + '">' + 
								'<td style=" text-align:left; width:50%;" valign="top" class="name_package">' + 
									'<?php echo '<b>' . __('There is a new version:') . '</b><br><br> '; ?>' +
									data['package'] +
								'</td>' +
								'<td style=" text-align:left; width:30%;" valign="bottom" class="timestamp_package">' +
									data['timestamp'] +
								'</td>' +
								'<td style=" text-align:center; width:50px;" valign="bottom">' +
								buttonUpdate +
								'</td>' +
							'</tr>');
						
						last = data['last'];
						
						if (data['end'] == 1) {
							$(".spinner_row", "#online_packages").remove();
						}
						else {
							ajax_get_online_package_list_admin();
						}
					}
					else {
						$(".spinner_row", "#online_packages").remove();
						row_html = '<tr class="package_' + data['package'] + '">' + 
							'<td style=" text-align:left; width:80%;" class="name_package">' +
							data['package'] +
							'</td>' +
							'<td style=" text-align:center; width:50px;"></td>' +
							'</tr>';
						console.log(row_html);
						$("tbody", "#online_packages").append(row_html); return;
						$("tbody", "#online_packages").append(
							);
					}
				}
			});
		}
	</script>
	<?php
}

function install_offline_enterprise_package(&$settings, $user_key) {
	global $config;
	
	if (isset($_FILES["fileloaded"]["error"])
		&& !$_FILES["fileloaded"]["error"]) {
		$extension = substr($_FILES["fileloaded"]["name"],
			strlen($_FILES["fileloaded"]["name"])-4, 4);
		
		if ($extension != '.oum') {
			ui_print_error_message(__('Incorrect file extension'));
		}
		else {
			$tempDir = sys_get_temp_dir()."/tmp_oum/";
			
			$zip = new ZipArchive;
			if ($zip->open($_FILES["fileloaded"]['tmp_name']) === TRUE) {
				$zip->extractTo($tempDir);
				$zip->close();
			}
			else {
				$error = ui_print_error_message(__('Update cannot be opened'));
			}
			
			$package = um_package_info_from_paths ($tempDir);
			if ($package === false) {
				ui_print_error_message(
					__('Error, the file package is empty or corrupted.'));
			}
			else {
				$settings = um_db_load_settings ();
				
				if ($settings->current_update >= $package->id) {
					ui_print_error_message(
					__('Your system version is higher or equal than the loaded package'));
				}
				else {
					$binary_paths = um_client_get_files ($tempDir."binary/");
					
					foreach ($binary_paths as $key => $paths) {
						foreach($paths as $index => $path) {
							$tempDir_scaped = preg_replace('/\//', '\/', $tempDir."binary");
							$binary_paths[$key][$index] = preg_replace('/^'.$tempDir_scaped.'/', ' ', $path);
						}
					}
					
					$code_paths = um_client_get_files ($tempDir."code/");
					
					foreach ($code_paths as $key => $paths) {
						foreach($paths as $index => $path) {
							$tempDir_scaped = preg_replace('/\//', '\/', $tempDir."code");
							$code_paths[$key][$index] = preg_replace('/^'.$tempDir_scaped.'/', ' ', $path);
						}
					}
					
					$sql_paths = um_client_get_files ($tempDir);
					foreach ($sql_paths as $key => $paths) {
						foreach ($paths as $index => $path) {
							if ($path != $tempDir || ($key == 'info_package' && $path == $tempDir)) {
								unset($sql_paths[$key]);
							}
						}
					}
					
					$updates_binary = array();
					$updates_code = array();
					$updates_sql = array();
					
					if (!empty($binary_paths)) {
						$updates_binary = um_client_update_from_paths ($binary_paths, $tempDir, $package->id, 'binary');
					}
					if (!empty($code_paths)) {
						$updates_code = um_client_update_from_paths ($code_paths, $tempDir, $package->id, 'code');
					}
					if (!empty($sql_paths)) {
						$updates_sql = um_client_update_from_paths ($sql_paths, $tempDir, $package->id, 'sql');
					}
					
					um_delete_directory($tempDir);
					
					$updates= array_merge((array) $updates_binary, (array) $updates_code, (array) $updates_sql);
					
					$package->updates = $updates;
					
					$settings = um_db_load_settings ();
					
					if (um_client_upgrade_to_package($package, $settings, true)) {
						ui_print_success_message(
							__('Successfully upgraded'));
						
						//Refresh the settings object.
						$settings = um_db_load_settings ();
					}
					else {
						ui_print_error_message(__('Cannot be upgraded'));
					}
				}
			}
		}
	}
	else {
		ui_print_error_message(__('File cannot be uploaded'));
	}
}
?>
