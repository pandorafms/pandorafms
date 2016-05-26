<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage UI
 */

function update_manager_get_config_values() {
	global $config;
	global $build_version;
	global $pandora_version;
	
	$license = db_get_value(
		db_escape_key_identifier('value'),
		'tupdate_settings',
		db_escape_key_identifier('key'),
		'customer_key');
	
	$limit_count = db_get_value_sql("SELECT count(*) FROM tagente");
	
	return array(
		'license' => $license,
		'current_update' => update_manager_get_current_package(),
		'limit_count' => $limit_count,
		'build' => $build_version,
		'version' => $pandora_version,
		);
}

//Function to remove dir and files inside
function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir . "/" . $object) == "dir")
					rrmdir($dir . "/" . $object);
				else unlink($dir . "/" . $object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}

function update_manager_install_package_step2() {
	global $config;
	
	ob_clean();
	
	$package = (string) get_parameter("package");
	$package = trim($package);
	
	$version = (string)get_parameter("version", 0);
	
	$path = sys_get_temp_dir() . "/pandora_oum/" . $package;
	
	// All files extracted
	$files_total = $path."/files.txt";
	// Files copied
	$files_copied = $path."/files.copied.txt";
	$return = array();
	
	if (file_exists($files_copied)) {
		unlink($files_copied);
	}
	
	if (file_exists($path)) {
		
		if ($files_h = fopen($files_total, "r")) {
			
			while ($line = stream_get_line($files_h, 65535, "\n")) {
				
				$line = trim($line);
				
				// Tries to move the old file to the directory backup inside the extracted package
				if (file_exists($config["homedir"]."/".$line)) {
					rename($config["homedir"]."/".$line, $path."/backup/".$line);
				}
				// Tries to move the new file to the Integria directory
				$dirname = dirname($line);
				if (!file_exists($config["homedir"]."/".$dirname)) {
					$dir_array = explode("/", $dirname);
					$temp_dir = "";
					foreach ($dir_array as $dir) {
						$temp_dir .= "/".$dir;
						if (!file_exists($config["homedir"].$temp_dir)) {
							mkdir($config["homedir"].$temp_dir);
						}
					}
				}
				if (is_dir($path."/".$line)) {
					if (!file_exists($config["homedir"]."/".$line)) {
						mkdir($config["homedir"]."/".$line);
						file_put_contents($files_copied, $line."\n", FILE_APPEND | LOCK_EX);
					}
				}
				else {
					//Copy the new file
					if (rename($path."/".$line, $config["homedir"]."/".$line)) {
						
						// Append the moved file to the copied files txt
						if (!file_put_contents($files_copied, $line."\n", FILE_APPEND | LOCK_EX)) {
							
							// If the copy process fail, this code tries to restore the files backed up before
							if ($files_copied_h = fopen($files_copied, "r")) {
								while ($line_c = stream_get_line($files_copied_h, 65535, "\n")) {
									$line_c = trim($line_c);
									if (!rename($path."/backup/".$line, $config["homedir"]."/".$line_c)) {
										$backup_status = __("Some of your files might not be recovered.");
									}
								}
								if (!rename($path."/backup/".$line, $config["homedir"]."/".$line)) {
									$backup_status = __("Some of your files might not be recovered.");
								}
								fclose($files_copied_h);
							} else {
								$backup_status = __("Some of your old files might not be recovered.");
							}
							
							fclose($files_h);
							$return["status"] = "error";
							$return["message"]= __("Line '$line' not copied to the progress file.")."&nbsp;".$backup_status;
							echo json_encode($return);
							return;
						}
					}
					else {
						// If the copy process fail, this code tries to restore the files backed up before
						if ($files_copied_h = fopen($files_copied, "r")) {
							while ($line_c = stream_get_line($files_copied_h, 65535, "\n")) {
								$line_c = trim($line_c);
								if (!rename($path."/backup/".$line, $config["homedir"]."/".$line)) {
									$backup_status = __("Some of your old files might not be recovered.");
								}
							}
							fclose($files_copied_h);
						}
						else {
							$backup_status = __("Some of your files might not be recovered.");
						}
						
						fclose($files_h);
						$return["status"] = "error";
						$return["message"]= __("File '$line' not copied.")."&nbsp;".$backup_status;
						echo json_encode($return);
						return;
					}
				}
			}
			fclose($files_h);
		}
		else {
			$return["status"] = "error";
			$return["message"]= __("An error ocurred while reading a file.");
			echo json_encode($return);
			return;
		}
	}
	else {
		$return["status"] = "error";
		$return["message"]= __("The package does not exist");
		echo json_encode($return);
		return;
	}
	
	$return["status"] = "success";
	$return["message"]= __("The package is installed.");
	echo json_encode($return);
	
	update_manager_enterprise_set_version($version);
	
	return;
}

function update_manager_main() {
	global $config;
	
	?>
	<script type="text/javascript">
		<?php
		echo "var unknown_error_update_manager = \"" .
			__('There is a unknown error.') . "\";";
		?>
	</script>
	<script src="include/javascript/update_manager.js"></script>
	<script type="text/javascript">
		var version_update = "";
		var stop_check_progress = 0;
		
		$(document).ready(function() {
			check_online_free_packages();
		});
	</script>
	<?php
}

function update_manager_check_online_free_packages_available() {
	global $config;
	
	$update_message = '';
	
	$um_config_values = update_manager_get_config_values();
	
	$params = array('action' => 'newest_package',
		'license' => $um_config_values['license'],
		'limit_count' => $um_config_values['limit_count'],
		'current_package' => $um_config_values['current_update'],
		'version' => $um_config_values['version'],
		'build' => $um_config_values['build']);
	
	$curlObj = curl_init();
	curl_setopt($curlObj, CURLOPT_URL, $config['url_update_manager']);
	curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlObj, CURLOPT_POST, true);
	curl_setopt($curlObj, CURLOPT_POSTFIELDS, $params);
	curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curlObj, CURLOPT_CONNECTTIMEOUT, 4);
	
	if (isset($config['update_manager_proxy_server'])) {
		curl_setopt($curlObj, CURLOPT_PROXY, $config['update_manager_proxy_server']);
	}
	if (isset($config['update_manager_proxy_port'])) {
		curl_setopt($curlObj, CURLOPT_PROXYPORT, $config['update_manager_proxy_port']);
	}
	if (isset($config['update_manager_proxy_user'])) {
		curl_setopt($curlObj, CURLOPT_PROXYUSERPWD, $config['update_manager_proxy_user'] . ':' . $config['update_manager_proxy_password']);
	}
	
	$result = curl_exec($curlObj);
	$http_status = curl_getinfo($curlObj, CURLINFO_HTTP_CODE);
	curl_close($curlObj);
	
	//~ html_debug_print("----------", true);
	//~ html_debug_print($params, true);
	//~ html_debug_print($http_status, true);
	//~ html_debug_print($result, true);
	
	if ($result === false) {
		return false;
	}
	elseif ($http_status >= 400 && $http_status < 500) {
		return false;
	}
	elseif ($http_status >= 500) {
		return false;
	}
	else {
		$result = json_decode($result, true);
		
		if (empty($result)) {
			return false;
		}
		else {
			return true;
		}
	}
}

function update_manager_check_online_free_packages ($is_ajax=true) {
	global $config;
	
	$update_message = '';
	
	$um_config_values = update_manager_get_config_values();
	
	$params = array('action' => 'newest_package',
		'license' => $um_config_values['license'],
		'limit_count' => $um_config_values['limit_count'],
		'current_package' => $um_config_values['current_update'],
		'version' => $um_config_values['version'],
		'build' => $um_config_values['build']);
	
	
	//For to test in the shell
	/*
	wget https://artica.es/pandoraupdate6/server.php -O- --no-check-certificate --post-data "action=newest_package&license=PANDORA_FREE&limit_count=1&current_package=1&version=v5.1RC1&build=PC140625"
	*/
	
	$curlObj = curl_init();
	curl_setopt($curlObj, CURLOPT_URL, $config['url_update_manager']);
	curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlObj, CURLOPT_POST, true);
	curl_setopt($curlObj, CURLOPT_POSTFIELDS, $params);
	curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, false);
	if (isset($config['update_manager_proxy_server'])) {
		curl_setopt($curlObj, CURLOPT_PROXY, $config['update_manager_proxy_server']);
	}
	if (isset($config['update_manager_proxy_port'])) {
		curl_setopt($curlObj, CURLOPT_PROXYPORT, $config['update_manager_proxy_port']);
	}
	if (isset($config['update_manager_proxy_user'])) {
		curl_setopt($curlObj, CURLOPT_PROXYUSERPWD, $config['update_manager_proxy_user'] . ':' . $config['update_manager_proxy_password']);
	}
	
	$result = curl_exec($curlObj);
	$http_status = curl_getinfo($curlObj, CURLINFO_HTTP_CODE);
	curl_close($curlObj);
	
	//~ html_debug_print("=============", true);
	//~ html_debug_print($params, true);
	//~ html_debug_print($http_status, true);
	//~ html_debug_print($result, true);
	
	if ($result === false) {
		if ($is_ajax) {
			echo __("Could not connect to internet");
		}
		else {
			$update_message = __("Could not connect to internet");
		}
	}
	else if ($http_status >= 400 && $http_status < 500) {
		if ($is_ajax) {
			echo __("Server not found.");
		}
		else {
			$update_message = __("Server not found.");
		}
	}
	elseif ($http_status >= 500) {
		if ($is_ajax) {
			echo $result;
		}
		else {
			$update_message = $result;
		}
	}
	else {
		if ($is_ajax) {
			$result = json_decode($result, true);
			
			if (!empty($result)) {
				echo "<p><b>There is a new version:</b> " . $result[0]['version'] . "</p>";
				echo "<a href='javascript: update_last_package(\"" . base64_encode($result[0]["file_name"]) .
					"\", \"" . $result[0]['version'] ."\");'>" .
					__("Update to the last version") . "</a>";
			}
			else {
				echo __("There is no update available.");
			}
			return;
		} else {
			if (!empty($result)) {
				$result = json_decode($result, true);
				$update_message = "There is a new version: " . $result[0]['version'];
			}
			
			return $update_message;
		}
	}
	
}


/**
 * The update copy entirire the tgz or fail (leave some parts copies and some part not).
 * This does make any thing with the BD.
 */
function update_manager_starting_update() {
	global $config;
	
	$path_package = $config['attachment_store'] .
		"/downloads/last_package.tgz";
	
	$full_path = $config['attachment_store'] . "/downloads/pandora_console";
	rrmdir($full_path);
	
	
	ob_start();
	$result = system("tar xvzf " . $path_package .
		" -C " . $config['attachment_store'] . "/downloads/", $none);
	ob_end_clean();
	
	if ($result != 0) {
		db_process_sql_update('tconfig',
			array('value' => json_encode(
					array(
						'status' => 'fail',
						'message' => __('Failed extracting the package to temp directory.')
					)
				)
			),
			array('token' => 'progress_update_status'));
		
		return false;
	}
	
	db_process_sql_update('tconfig',
		array('value' => 50),
		array('token' => 'progress_update'));
	
	
	
	$homedir = $config['homedir'];
	
	$result = update_manager_recurse_copy(
		$full_path,
		$homedir,
		array('install.php'));
	
	if (!$result) {
		db_process_sql_update('tconfig',
			array('value' => json_encode(
					array(
						'status' => 'fail',
						'message' => __('Failed the copying of the files.')
					)
				)
			),
			array('token' => 'progress_update_status'));
		
		return false;
	}
	else {
		db_process_sql_update('tconfig',
			array('value' => 100),
			array('token' => 'progress_update'));
		db_process_sql_update('tconfig',
			array('value' => json_encode(
					array(
						'status' => 'end',
						'message' => __('Package extracted successfully.')
					)
				)
			),
			array('token' => 'progress_update_status'));
		
		return true;
	}
}


function update_manager_recurse_copy($src, $dst, $black_list) { 
	$dir = opendir($src); 
	@mkdir($dst);
	@trigger_error("NONE");
	
	//debugPrint("mkdir(" . $dst . ")", true);
	while (false !== ( $file = readdir($dir)) ) { 
		if (( $file != '.' ) && ( $file != '..' ) && (!in_array($file, $black_list))) { 
			if ( is_dir($src . '/' . $file) ) { 
				if (!update_manager_recurse_copy($src . '/' . $file,$dst . '/' . $file, $black_list)) {
					return false;
				}
			}
			else { 
				$result = copy($src . '/' . $file,$dst . '/' . $file);
				$error = error_get_last();
				
				if (strstr($error['message'], "copy(") ) {
					return false;
				}
			} 
		} 
	} 
	closedir($dir);
	
	return true;
}

function update_manager_set_current_package($current_package) {
	if (enterprise_installed()) {
		$token = 'current_package_enterprise';
	}
	else {
		$token = 'current_package';
	}
	
	$col_value = db_escape_key_identifier('value');
	$col_key = db_escape_key_identifier('key');
	
	$value = db_get_value($col_value,
		'tupdate_settings', $col_key, $token);
	
	if ($value === false) {
		db_process_sql_insert('tupdate_settings',
			array($col_value => $current_package, $col_key => $token));
	}
	else {
		db_process_sql_update('tupdate_settings',
			array($col_value => $current_package),
			array($col_key => $token));
	}
}

function update_manager_get_current_package() {
	global $config;
	
	if (enterprise_installed()) {
		$token = 'current_package_enterprise';
	}
	else {
		$token = 'current_package';
	}
	$current_update = db_get_value(
		db_escape_key_identifier('value'),
		'tupdate_settings',
		db_escape_key_identifier('key'),
		$token);
	
	if ($current_update === false) {
		$current_update = 0;
		if (isset($config[$token]))
			$current_update = $config[$token];
	}
	
	return $current_update;
}

// Set the read or not read status message of current user
function update_manger_set_read_message ($message_id, $status) {
	global $config;
	
	$rollback = db_get_value('data_rollback', 'tupdate', 'svn_version', $message_id);
	$users_read = json_decode ($rollback, true);
	$users_read[$config['id_user']] = $status;
	
	$rollback = json_encode ($users_read);
	db_process_sql_update('tupdate', array('data_rollback' => $rollback), array('svn_version' => $message_id));	
}

// Get the read or not read status message
function update_manger_get_read_message ($message_id, $rollback = false) {
	global $config;
	
	if ($rollback === false) {
		$rollback = db_get_value('data_rollback', 'tupdate', 'svn_version', $message_id);
	}
	$users_read = json_decode ($rollback, true);
	
	if (isset ($users_read[$config['id_user']]) && ($users_read[$config['id_user']] == 1)) {
		return true;
	}
	return false;
}
?>
