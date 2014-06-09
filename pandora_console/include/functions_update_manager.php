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
	$license = db_get_value('`value`', 'tupdate_settings', '`key`',
		'customer_key');
	$current_update = db_get_value('`value`', 'tupdate_settings', '`key`',
		'current_update');
	$limit_count = db_get_value_sql("SELECT count(*) FROM tagente");
	global $build_version;
	global $pandora_version;
	
	//TO DO
	$license = "TESTMIGUEL00B0WAW9BU1QM0RZ2QM0MZ3QN5M41R35S5S1DP";
	$current_update = 11;
	
	return array(
		'license' => $license,
		'current_update' => $current_update,
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
				if (filetype($dir."/".$object) == "dir")
					rrmdir($dir."/".$object); else unlink($dir."/".$object);
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
	
}
?>