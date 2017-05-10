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
	$remove_rr = get_parameter('remove_rr', 0);
	$remove_rr_extras = get_parameter('remove_rr_extras', 0);
	
	if ($updare_rr) {
		$number = get_parameter('number');
		$package = get_parameter('package');
		$ent = get_parameter('ent');
		$offline = get_parameter('offline');
		if (!$ent) {
			$dir = $config['attachment_store'] . "/downloads/pandora_console/extras/mr";
		}
		else {
			if ($offline) {
				$dir = $package . "/extras/mr";
			}
			else {
				$dir = sys_get_temp_dir() . "/pandora_oum/" . $package . "/extras/mr";
			}
		}
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
						if (!file_exists($config["homedir"] . "/extras/mr/updated") || !is_dir($config["homedir"] . "/extras/mr/updated")) {
							mkdir($config["homedir"] . "/extras/mr/updated");
						}
						
						$file_dest = $config["homedir"] . "/extras/mr/updated/$number.sql";
						copy($file, $file_dest);
												
						$message = "bad_mr_filename";
					}
					else {
						$result = db_run_sql_file($file);

						if ($result) {
							$update_config = update_config_token("MR", $number);
							if ($update_config) {
								$config["MR"] = $number;
							}
							
							if ($config["MR"] == $number) {
								if (!file_exists($config["homedir"] . "/extras/mr/updated") || !is_dir($config["homedir"] . "/extras/mr/updated")) {
									mkdir($config["homedir"] . "/extras/mr/updated");
								}
								
								$file_dest = $config["homedir"] . "/extras/mr/updated/$number.sql";
								
								copy($file, $file_dest);
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

	if ($remove_rr) {
        $numbers = get_parameter('number',0);

        foreach ($numbers as $number) {
            for ($i = 1; $i <= $number; $i++) {
                $file = $config["homedir"] . "/extras/mr/$i.sql";
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        return;
    }

	if ($remove_rr_extras) {
		$dir = $config["homedir"] . "/extras/mr/";
		
		if (file_exists($dir) && is_dir($dir)) {
			if (is_readable($dir)) {
				$files = scandir($dir); // Get all the files from the directory ordered by asc

				if ($files !== false) {
					$pattern = "/^\d+\.sql$/";
					$sqlfiles = preg_grep($pattern, $files); // Get the name of the correct files
					$files = null;
					$pattern = "/\.sql$/";
					$replacement = "";
					$sqlfiles_num = preg_replace($pattern, $replacement, $sqlfiles); // Get the number of the file
					
					foreach ($sqlfiles_num as $num) {
						$file = $dir . "$num.sql";
						if (file_exists($file)) {
							unlink($file);
						}
					}
				}
			}
		}
		return;
	}
}

?>
