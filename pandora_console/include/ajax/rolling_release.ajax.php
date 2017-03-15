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

	$updare_rr = get_parameter('updare_rr', 0);
	
	if ($updare_rr) {
		$number = get_parameter('number');

		$dir = $config["homedir"]."/extras/mr";
		
		$file = "$dir/$number.sql";
		
		$dangerous_query = false;
		$mr_file = fopen($file, "r");
		while (!feof($mr_file)) {
			$line = fgets($mr_file);
			if ((preg_match("/^drop/", $line)) || 
				(preg_match("/^truncate table/", $line))) {
				$dangerous_query = true;
			}
		}
		if ($dangerous_query) {
			$error_file = fopen($config["homedir"] . "/extras/mr/error.txt", "w");

			$message = "<div>";
			$message .= "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_error_mr.png'></div>";
			$message .= "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>ERROR</strong></h3>";
			$message .= "<p style='font-family:Verdana; font-size:12pt;'>" . __('The sql file contains a dangerous query') . "</p></div>";
			$message .= "</div>";

			fwrite($error_file, $message);
			fclose($error_file);
		}
		else {
			if (file_exists($dir) && is_dir($dir)) {
				if (is_readable($dir)) {
					if (($number > $config['MR'] + 1) || ($number == $config['MR'])) {
						$message = "bad_mr_filename";
					}
					else if ($config["MR"] > $number) {
						if (!file_exists($dir."/updated") || !is_dir($dir."/updated")) {
							mkdir($dir."/updated");
						}
						$file_dest = "$dir/updated/$number.sql";
						if (copy($file, $file_dest)) {
							unlink($file);
						}
					}
					else {
						$result = db_run_sql_file($file);
						if ($result) {
							$update_config = update_config_token("MR", $number);
							if ($update_config) {
								$config["MR"] = $number;
							}
							
							if ($config["MR"] == $number) {
								if (!file_exists($dir."/updated") || !is_dir($dir."/updated")) {
									mkdir($dir."/updated");
								}
								
								$file_dest = "$dir/updated/$number.sql";
								
								if (copy($file, $file_dest)) {
									unlink($file);
								}
							}
						}
						else {
							$error_file = fopen($config["homedir"] . "/extras/mr/error.txt", "w");

							$message = "<div>";
							$message .= "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_error_mr.png'></div>";
							$message .= "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>ERROR</strong></h3>";
							$message .= "<p style='font-family:Verdana; font-size:12pt;'>" . __('An error occurred while updating the database schema to the minor release ') . $number . "</p></div>";
							$message .= "</div>";

							fwrite($error_file, $message);
							fclose($error_file);
						}
					}
				}
				else {
					$error_file = fopen($config["homedir"] . "/extras/mr/error.txt", "w");

					$message = "<div>";
					$message .= "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_error_mr.png'></div>";
					$message .= "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>ERROR</strong></h3>";
					$message .= "<p style='font-family:Verdana; font-size:12pt;'>" . __('The directory ') . $dir . __(' should have read permissions in order to update the database schema') . "</p></div>";
					$message .= "</div>";

					fwrite($error_file, $message);
					fclose($error_file);
				}
			}
			else {
				$error_file = fopen($config["homedir"] . "/extras/mr/error.txt", "w");

				$message = "<div>";
				$message .= "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_error_mr.png'></div>";
				$message .= "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>ERROR</strong></h3>";
				$message .= "<p style='font-family:Verdana; font-size:12pt;'>" . __('The directory ') . $dir . __(' does not exist') . "</p></div>";
				$message .= "</div>";

				fwrite($error_file, $message);
				fclose($error_file);
			}
		}
		
		echo $message;
		return;
	}
}

?>
