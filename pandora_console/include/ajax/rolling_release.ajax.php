<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Only accesible by ajax
if (is_ajax ()) {
	global $config;
	
	check_login();

	$updare_rr_open = get_parameter('updare_rr_open', 0);
	$check_minor_number = get_parameter('check_minor_number', 0);
	$check_finish = get_parameter('check_finish', 0);
	
	if ($updare_rr_open) {
		$number = get_parameter('number');
		$dir = $config["homedir"]."/extras/mr";
		
		$file = "$dir/$number.open.sql";
		
		$dangerous_query = false;
		$mr_file = fopen($file, "r");
		while (!feof($mr_file)) {
			$line = fgets($mr_file);
			if ((preg_match("/^drop/", $line)) || 
				(preg_match("/^DROP/", $line))) {
				$dangerous_query = true;
			}
		}
		
		if ($dangerous_query) {
			$error_file = fopen($config["homedir"] . "/extras/mr/error.txt", "w");
			$message = "The sql file contains a dangerous query";
			fwrite($error_file, $message);
			fclose($error_file);
		}
		else {
			if (file_exists($dir) && is_dir($dir)) {
				if (is_readable($dir)) {
					if ($config["minor_release_open"] >= $number) {
						if (!file_exists($dir."/updated") || !is_dir($dir."/updated")) {
							mkdir($dir."/updated");
						}
						$file_dest = "$dir/updated/$number.open.sql";
						if (copy($file, $file_dest)) {
							unlink($file);
						}
					}
					else {
						$result = db_run_sql_file($file);
						
						if ($result) {
							$update_config = update_config_token("minor_release_open", $number);
							if ($update_config) {
								$config["minor_release_open"] = $number;
							}
							
							if ($config["minor_release_open"] == $number) {
								if (!file_exists($dir."/updated") || !is_dir($dir."/updated")) {
									mkdir($dir."/updated");
								}
								
								$file_dest = "$dir/updated/$number.open.sql";
								
								if (copy($file, $file_dest)) {
									unlink($file);
								}
							}
						}
						else {
							$error_file = fopen($config["homedir"] . "/extras/mr/error.txt", "w");
							$message = "An error occurred while updating the database schema to the minor release " . $number;
							fwrite($error_file, $message);
							fclose($error_file);
						}
					}
				}
				else {
					$error_file = fopen($config["homedir"] . "/extras/mr/error.txt", "w");
					$message = "The directory ' . $dir . ' should have read permissions in order to update the database schema";
					fwrite($error_file, $message);
					fclose($error_file);
				}
			}
			else {
				$error_file = fopen($config["homedir"] . "/extras/mr/error.txt", "w");
				$message = "The directory ' . $dir . ' does not exist";
				fwrite($error_file, $message);
				fclose($error_file);
			}
		}
		
		echo $message;
		return;
	}
	else if ($check_finish) {
		$check = db_check_minor_relase_available();
		
		if (file_exists($config["homedir"] . "/extras/mr/error.txt")) {
			unlink($config["homedir"] . "/extras/mr/error.txt");
			$check = 2;
		}
		
		echo $check;
		return;
	}
	else if ($check_minor_number) {
		echo $config['minor_release_open'];
		return;
	}
}

?>
