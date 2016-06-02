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

global $config;

require_once($config['homedir'] . "/include/functions_update_manager.php");
require_once($config['homedir'] . "/include/functions_graph.php");
enterprise_include_once("include/functions_update_manager.php");

$upload_file = (boolean) get_parameter("upload_file");
$install_package = (boolean) get_parameter("install_package");
$check_install_package = (boolean) get_parameter("check_install_package");
$check_online_packages = (boolean) get_parameter("check_online_packages");
$check_online_enterprise_packages = (boolean) get_parameter("check_online_enterprise_packages");
$update_last_package = (boolean) get_parameter("update_last_package");
$update_last_enterprise_package = (boolean) get_parameter("update_last_enterprise_package");
$install_package_online = (boolean) get_parameter("install_package_online");
$check_progress_update = (boolean) get_parameter("check_progress_update");
$check_progress_enterprise_update = (boolean) get_parameter("check_progress_enterprise_update");
$install_package_step2 = (boolean)get_parameter("install_package_step2");
$enterprise_install_package = (boolean) get_parameter("enterprise_install_package");
$enterprise_install_package_step2 = (boolean)get_parameter("enterprise_install_package_step2");
$check_online_free_packages = (bool)get_parameter('check_online_free_packages');
$update_last_free_package = (bool)get_parameter('update_last_free_package');
$check_update_free_package = (bool)get_parameter('check_update_free_package');
$install_free_package = (bool)get_parameter('install_free_package');

if ($upload_file) {
	ob_clean();
	$return = array();
	
	if (isset($_FILES['upfile']) && $_FILES['upfile']['error'] == 0) {
		
		$extension = pathinfo($_FILES['upfile']['name'], PATHINFO_EXTENSION);
		
		// The package extension should be .oum
		if (strtolower($extension) === "oum") {
			
			$path = $_FILES['upfile']['tmp_name'];
			// The package files will be saved in [user temp dir]/pandora_oum/package_name
			$destination = sys_get_temp_dir()."/pandora_oum/".$_FILES['upfile']['name'];
			// files.txt will have the names of every file of the package
			if (file_exists($destination."/files.txt")) {
				unlink($destination."/files.txt");
			}
			
			$zip = new ZipArchive;
			// Zip open
			if ($zip->open($path) === true) {
				// The files will be extracted one by one
				for($i = 0; $i < $zip->numFiles; $i++) {
					$filename = $zip->getNameIndex($i);
					
					if ($zip->extractTo($destination, array($filename))) {
						// Creates a file with the name of the files extracted
						file_put_contents ($destination."/files.txt", $filename."\n", FILE_APPEND | LOCK_EX);
					} else {
						// Deletes the entire extraction directory if a file can not be extracted
						delete_directory($destination);
						$return["status"] = "error";
						$return["message"] = __("There was an error extracting the file '".$filename."' from the package.");
						echo json_encode($return);
						return;
					}
				}
				// Creates a file with the number of files extracted
				file_put_contents ($destination."/files.info.txt", $zip->numFiles);
				// Zip close
				$zip->close();
				
				$return["status"] = "success";
				$return["package"] = $destination;
				echo json_encode($return);
				return;
			} else {
				$return["status"] = "error";
				$return["message"] = __("The package was not extracted.");
				echo json_encode($return);
				return;
			}
		} else {
			$return["status"] = "error";
			$return["message"] = __("Invalid extension. The package must have the extension .oum.");
			echo json_encode($return);
			return;
		}
	}
	
	$return["status"] = "error";
	$return["message"] = __("The file was not uploaded succesfully.");
	echo json_encode($return);
	return;
}

if ($install_package) {
	global $config;
	ob_clean();
	
	$package = (string) get_parameter("package");
	$package = trim($package);
	
	$chunks = explode("_", basename($package));
	$chunks = explode(".", $chunks[1]);
	$version = $chunks[0];
	
	// All files extracted
	$files_total = $package . "/files.txt";
	// Files copied
	$files_copied = $package . "/files.copied.txt";
	$return = array();
	
	if (file_exists($files_copied)) {
		unlink($files_copied);
	}
	
	if (file_exists($package)) {
		
		if ($files_h = fopen($files_total, "r")) {
			
			while ($line = stream_get_line($files_h, 65535, "\n")) {
				$line = trim($line);
				
				// Tries to move the old file to the directory backup inside the extracted package
				if (file_exists($config["homedir"] . "/" . $line)) {
					rename($config["homedir"] . "/" . $line,
						$package . "/backup/" . $line);
				}
				// Tries to move the new file to the Pandora directory
				$dirname = dirname($line);
				if (!file_exists($config["homedir"] . "/" . $dirname)) {
					$dir_array = explode("/", $dirname);
					$temp_dir = "";
					foreach ($dir_array as $dir) {
						$temp_dir .= "/" . $dir;
						if (!file_exists($config["homedir"] . $temp_dir)) {
							mkdir($config["homedir"] . $temp_dir);
						}
					}
				}
				if (is_dir($package . "/" . $line)) {
					if (!file_exists($config["homedir"] . "/" . $line)) {
						mkdir($config["homedir"] . "/" . $line);
						file_put_contents($files_copied, $line."\n", FILE_APPEND | LOCK_EX);
					}
				}
				else {
					if (rename($package."/".$line, $config["homedir"]."/".$line)) {
						
						// Append the moved file to the copied files txt
						if (!file_put_contents($files_copied, $line."\n", FILE_APPEND | LOCK_EX)) {
							
							// If the copy process fail, this code tries to restore the files backed up before
							if ($files_copied_h = fopen($files_copied, "r")) {
								while ($line_c = stream_get_line($files_copied_h, 65535, "\n")) {
									$line_c = trim($line_c);
									if (!rename($package."/backup/".$line, $config["homedir"]."/".$line_c)) {
										$backup_status = __("Some of your files might not be recovered.");
									}
								}
								if (!rename($package."/backup/".$line, $config["homedir"]."/".$line)) {
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
								if (!rename($package."/backup/".$line, $config["homedir"]."/".$line)) {
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
	
	update_manager_enterprise_set_version($version);
	db_pandora_audit("Update Pandora", "Update version: $version of Pandora FMS by ".$config['id_user']);
	
	$return["status"] = "success";
	echo json_encode($return);
	return;
}

if ($check_install_package) {
	// 1 second
	//sleep(1);
	// Half second
	usleep(500000);
	
	ob_clean();
	
	$package = (string) get_parameter("package");
	// All files extracted
	$files_total = $package."/files.txt";
	// Number of files extracted
	$files_num = $package."/files.info.txt";
	// Files copied
	$files_copied = $package."/files.copied.txt";
	
	$files = @file($files_copied);
	if (empty($files))
		$files = array();
	$total = (int)@file_get_contents($files_num);
	
	$progress = 0;
	if ((count($files) > 0) && ($total > 0)) {
		$progress = format_numeric((count($files) / $total) * 100, 2);
		if ($progress > 100)
			$progress = 100;
	}
	
	$return = array();
	$return['info'] = (string) implode("<br />", $files);
	$return['progress'] = $progress;
	
	if ($progress >= 100) {
		unlink($files_total);
		unlink($files_num);
		unlink($files_copied);
	}
	
	echo json_encode($return);
	return;
}

if ($check_online_enterprise_packages) {
	
	update_manager_check_online_enterprise_packages();
	
	return;
	
}

if ($check_online_packages) {
	
	return;
}

if ($update_last_enterprise_package) {
	
	update_manager_update_last_enterprise_package();
	
	return;
	
}

if ($update_last_package) {
	
	return;
}

if ($install_package_online) {
	
	return;
}

if ($install_package_step2) {
	update_manager_install_package_step2();
	
	return;
}

if ($check_progress_enterprise_update) {
	update_manager_check_progress_enterprise();
	
	return;
}

if ($check_progress_update) {
	
	return;
}

if ($enterprise_install_package) {
	$package = get_parameter('package', '');
	
	
	update_manager_enterprise_starting_update($package,
		$config['attachment_store'] . "/downloads/" . $package);
	
	return;
}

if ($enterprise_install_package_step2) {
	update_manager_install_enterprise_package_step2();
	
	return;
}

if ($check_online_free_packages) {
	
	update_manager_check_online_free_packages();
	
	return;
}

if ($update_last_free_package) {
	
	$package = get_parameter('package', '');
	$version = get_parameter('version', '');
	$package_url = base64_decode($package);
	
	$params = array('action' => 'get_package',
		'license' => $license,
		'limit_count' => $users,
		'current_package' => $current_package,
		'package' => $package,
		'version' => $config['version'],
		'build' => $config['build']);
	
	$curlObj = curl_init();
	//curl_setopt($curlObj, CURLOPT_URL, $config['url_updatemanager']);
	curl_setopt($curlObj, CURLOPT_URL, $package_url);
	curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlObj, CURLOPT_FOLLOWLOCATION, true);
	//curl_setopt($curlObj, CURLOPT_POST, true);
	//curl_setopt($curlObj, CURLOPT_POSTFIELDS, $params);
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
	
	
	
	
	
	if (empty($result)) {
		echo json_encode(array(
			'in_progress' => false,
			'message' => __('Fail to update to the last package.')));
	}
	else {
		file_put_contents(
			$config['attachment_store'] . "/downloads/last_package.tgz" , $result);
		
		echo json_encode(array(
			'in_progress' => true,
			'message' => __('Starting to update to the last package.')));
		
		
		$progress_update_status = db_get_value(
			'value', 'tconfig', 'token', 'progress_update_status');
		
		
		
		if (empty($progress_update_status)) {
			db_process_sql_insert('tconfig',
				array(
					'value' => 0,
					'token' => 'progress_update')
			);
			
			db_process_sql_insert('tconfig',
				array(
					'value' => json_encode(
						array(
							'status' => 'in_progress',
							'message' => ''
						)),
					'token' => 'progress_update_status')
				);
		}
		else {
			db_process_sql_update('tconfig',
				array('value' => 0),
				array('token' => 'progress_update'));
			
			db_process_sql_update('tconfig',
				array('value' => json_encode(
						array(
							'status' => 'in_progress',
							'message' => ''
						)
					)
				),
				array('token' => 'progress_update_status'));
		}
	}
	
	return;
}

if ($check_update_free_package) {
	
	
	$progress_update = db_get_value('value', 'tconfig',
		'token', 'progress_update');
	
	$progress_update_status = db_get_value('value', 'tconfig',
		'token', 'progress_update_status');
	$progress_update_status = json_decode($progress_update_status, true);
	
	switch ($progress_update_status['status']) {
		case 'in_progress':
			$correct = true;
			$end = false;
			break;
		case 'fail':
			$correct = false;
			$end = false;
			break;
		case 'end':
			$correct = true;
			$end = true;
			break;
	}
	
	$progressbar_tag = progressbar($progress_update, 400, 20,
		__("progress"), $config['fontpath']);
	preg_match("/src='(.*)'/", $progressbar_tag, $matches);
	$progressbar = $matches[1];
	
	echo json_encode(array(
		'correct' => $correct,
		'end' => $end,
		'message' => $progress_update_status['message'],
		'progressbar' => $progressbar
	));
	
	return;
}

if ($install_free_package) {
	$version = get_parameter('version', '');
	
	update_manager_starting_update();
	
	$result = update_manager_starting_update();
	
	if ($result)
		update_manager_set_current_package($version);
	
	
	sleep(3);
	
	
	
	$return["status"] = "success";
	$return["message"]= __("The package is installed.");
	echo json_encode($return);
}
?>
