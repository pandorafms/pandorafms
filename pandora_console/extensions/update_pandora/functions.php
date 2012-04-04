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
			'last_contact' => '');
		
		$values = array('token' => 'update_pandora_conf_url',
			'value' => $conf_update_pandora['url']);
		$return = db_process_sql_insert('tconfig', $values);
		$values = array('token' => 'update_pandora_conf_last_installed',
			'value' => $conf_update_pandora['last_installed']);
		$return = db_process_sql_insert('tconfig', $values);
		$values = array('token' => 'update_pandora_conf_last_contact',
			'value' => $conf_update_pandora['last_contact']);
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
					if ($conf_update_pandora['last_installed'] == $entry) {
						$packages[] = array('name' => $entry,
							'current' => true);
					}
					else {
						$packages[] = array('name' => $entry,
							'current' => false);
					}
					
					
				}
			}
		}
	}
	
	if (empty($packages)) {
		if ($mode == 'operation') {
			$packages[] = array('name' => 
				__('There are not downloaded packages in your Pandora Console.'));
		}
		else {
			$packages[] = array('empty' => true, 'name' =>
				__('There are not downloaded packages in your Pandora Console.'));
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
	$extension_php_file = 'extensions/update_pandora';
	
	?>
	<script type="text/javascript">
		var last = 0;
		$(document).ready(function() {
			ajax_get_online_package_list_admin();
			
			$("#submit-hide_download_dialog").click (function () {
				//Better than fill the downloaded packages
				location.reload();
				//$("#dialog_download" ).dialog('close');
			});
		});
		
		function ajax_start_install_package(package) {
			$(".package_name").html(package);
			
			$("#dialog_download").dialog({
					resizable: false,
					draggable: false,
					modal: true,
					height: 400,
					width: 600,
					overlay: {
							opacity: 0.5,
							background: "black"
						},
					bgiframe: jQuery.browser.msie
				});
			$(".ui-dialog-titlebar-close").hide();
			$("#dialog_download").show();
			
			$("#title_downloading_update_pandora").hide();
			$("#title_installing_update_pandora").show();
			
			install_package(package, package);
		}
		
		function ajax_start_download_package(package) {
			$(".package_name").html(package);
			
			$("#dialog_download").dialog({
					resizable: false,
					draggable: false,
					modal: true,
					height: 400,
					width: 600,
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
					if (data['correct'] == 1) {
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
					else {
						$("#title_downloading_update_pandora").hide();
						$("#title_error_update_pandora").show();
						$("#progress_bar_img").hide();
						
						$("#info_text").html('');
						
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
							$("#button_close_download").show();
						}
					}
				}
			})
		}
		
		function ajax_get_online_package_list_admin() {
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
						$("tbody", "#online_packages").append(
							'<tr class="package_' + data['package'] + '">' + 
								'<td style=" text-align:left; width:80%;" class="name_package">' +
									data['package'] +
								'</td>' +
								'<td style=" text-align:center; width:50px;">' +
								'<a href="javascript: ajax_start_download_package(\'' + data['package'] + '\')">' +
								'<?php html_print_image('images/down.png', false,
								array('alt' => __('Download and install'), 'title' => __('Download and install'))); ?>' +
								'</a>' +
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
					}
				}
			});
		}
	</script>
	<?php
}
?>
