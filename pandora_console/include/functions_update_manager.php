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
	else {
		unlink ($dir);
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
	
	update_manager_enterprise_set_version($version);
	db_pandora_audit("Update Pandora", "Update version: $version of Pandora FMS by ".$config['id_user']);
	
	$return["status"] = "success";
	$return["message"]= __("The package is installed.");
	echo json_encode($return);
	
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
	wget https://artica.es/pandoraupdate7/server.php -O- --no-check-certificate --post-data "action=newest_package&license=PANDORA_FREE&limit_count=1&current_package=1&version=v5.1RC1&build=PC140625"
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
				?>
				<script type="text/javascript">
					var mr_available = "<?php echo __('Minor release available'); ?>\n";
					var package_available = "<?php echo __('New package available'); ?>\n";
					var mr_not_accepted = "<?php echo __('Minor release rejected. Changes will not apply.'); ?>\n";
					var mr_not_accepted_code_yes = "<?php echo __('Minor release rejected. The database will not be updated and the package will apply.'); ?>\n";
					var mr_cancel = "<?php echo __('Minor release rejected. Changes will not apply.'); ?>\n";
					var package_cancel = "<?php echo __('These package changes will not apply.'); ?>\n";
					var package_not_accepted = "<?php echo __('Package rejected. These package changes will not apply.'); ?>\n";
					var mr_success = "<?php echo __('Database successfully updated'); ?>\n";
					var mr_error = "<?php echo __('Error in MR file'); ?>\n";
					var package_success = "<?php echo __('Package updated successfully'); ?>\n";
					var package_error = "<?php echo __('Error in package updated'); ?>\n";
					var bad_mr_file = "<?php echo __('Database MR version is inconsistent, do you want to apply the package?'); ?>\n";
					var text1_mr_file = "<?php echo __('There are a new database changes available to apply. Do you want to start the DB update process?'); ?>\n";
					var text2_mr_file = "<?php echo __('We recommend launch a '); ?>\n";
					var text3_mr_file = "<?php echo __('planned downtime'); ?>\n";
					var text4_mr_file = "<?php echo __(' to this process'); ?>\n";
					var text1_package_file = "<?php echo __('There is a new update available'); ?>\n";
					var text2_package_file = "<?php echo __('There is a new update available to apply. Do you want to start the update process?'); ?>\n";
					var applying_mr = "<?php echo __('Applying DB MR'); ?>\n";
				</script>
				<?php
				$baseurl = ui_get_full_url(false, false, false, false);
				echo "<p><b>There is a new version:</b> " . $result[0]['version'] . "</p>";
				echo "<a href='javascript: update_last_package(\"" . base64_encode($result[0]["file_name"]) .
					"\", \"" . $result[0]['version'] ."\", \"" . $baseurl ."\");'>" .
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

function update_manager_curl_request ($action, $additional_params = false) {
	global $config;
	
	$error_array = array ('success' => true, 'update_message' => '');
	$update_message = "";
	
	$um_config_values = update_manager_get_config_values();
	
	$params = array(
		'license' => $um_config_values['license'],
		'limit_count' => $um_config_values['limit_count'],
		'current_package' => $um_config_values['current_update'],
		'version' => $um_config_values['version'],
		'build' => $um_config_values['build']
	);
	if ($additional_params !== false) {
		$params = array_merge ($params, $additional_params);
	}
	$params['action'] = $action;
	
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
	
	$error_array['http_status'] = $http_status;
	
	if ($result === false) {
		$error_array['success'] = false;
		if ($is_ajax) {
			echo __("Could not connect to internet");
			return $error_array;
		}
		else {
			$error_array['update_message'] = __("Could not connect to internet");
			return $error_array;
		}
	}
	else if ($http_status >= 400 && $http_status < 500) {
		$error_array['success'] = false;
		if ($is_ajax) {
			echo __("Server not found.");
			return $error_array;
		}
		else {
			$error_array['update_message'] = __("Server not found.");
			return $error_array;
		}
	}
	elseif ($http_status >= 500) {
		$error_array['success'] = false;
		if ($is_ajax) {
			echo $result;
			return $error_array;
		}
		else {
			$error_array['update_message'] = $result;
			return $error_array;
		}
	}
	
	$error_array['update_message'] = $result;
	return $error_array;
	
}

function update_manager_insert_newsletter ($email) {
	global $config;
	
	if ($email === '') return false;
	
	$params = array(
		'email' => $email,
		'language' => $config['language']
		);
	
	$result = update_manager_curl_request ('new_newsletter', $params);
	
	if (!$result['success']) {
		return array('success' => false, 'message' => __('Remote server error on newsletter request'));
	}
	
	switch ($result['http_status']) {
		
		case 200:
			$message = json_decode($result['update_message'], true);
			if ($message['success'] == 1) {
				return array('success' => true, 'message' => __('E-mail successfully subscribed to newsletter.'));
			}
			return array('success' => false, 'message' => __('E-mail has already subscribed to newsletter.'));
		default:
			return array('success' => false, 'message' => __('Update manager returns error code: ') . $result['http_status'] . '.');
			break;			
	}
}

function update_manager_register_instance () {
	global $config;
	
	$email = db_get_value ('email', 'tusuario', 'id_user', $config['id_user']);
	$params = array(
		'language' => $config['language'],
		'timezone' => $config['timezone'],
		'email' => $email
		);
	
	$result = update_manager_curl_request ('new_register', $params);
	
	if (!$result['success']) {
		return array('success' => false, 'message' => __('Remote server error on newsletter request'));
	}
	
	switch ($result['http_status']) {
		case 200:
			//Retrieve the PUID
			$message = json_decode($result['update_message'], true);
			
			if ($message['success'] == 1) {
				$puid = $message['pandora_uid'];
				config_update_value ('pandora_uid', $puid);
				
				//The tupdate table is reused to display messages. A specific entry to tupdate_package is required. 
				//Then, this tupdate_package id is saved in tconfig
				db_process_sql_insert ('tupdate_package', array ('description' => '__UMMESSAGES__'));
				$id_um_package_messages = db_get_value('id', 'tupdate_package', 'description', '__UMMESSAGES__');
				config_update_value ('id_um_package_messages', $id_um_package_messages);
				return array('success' => true, 'message' => __('Pandora successfully subscribed with UID: ') . $puid . '.');
			}
			return array('success' => false, 'message' => __('Unsuccessful subscription.'));
			break;			
		default:
			return array('success' => false, 'message' => __('Update manager returns error code: ') . $result['http_status'] . '.');
			break;		
	}
}

function update_manager_download_messages () {
	include_once ("include/functions_io.php");
	global $config;
	
	if (!isset ($config['pandora_uid'])) return;
	//Do not ask in next 2 hours
	$future = time() + 2 * SECONDS_1HOUR;
	config_update_value ('last_um_check', $future);

	// Delete old messages
	//db_get_sql('DELETE FROM tupdate WHERE UNIX_TIMESTAMP(filename) < UNIX_TIMESTAMP(NOW())');
	
	// Build the curl request
	$params = array(
		'pandora_uid' => $config['pandora_uid'],
		'timezone' => $config['timezone'],
		'language' => $config['language']
	);
	
	$result = update_manager_curl_request ('get_messages', $params);
	
	if (!$result['success']) {
		return ($result['update_message']);
	}
	
	switch ($result['http_status']) {
		case 200:
			$message = json_decode($result['update_message'], true);
			
			if ($message['success'] == 1) {
				foreach ($message['messages'] as $single_message) {
					// Convert subject -> db_field_value; message_html -> data; expiration -> filename; message_id -> svn_version
					$single_message['db_field_value'] = io_safe_input($single_message['subject']);
					unset ($single_message['subject']);
					$single_message['data'] = io_safe_input_html($single_message['message_html']);
					// It is mandatory to prepend a backslash to all single quotes
					$single_message['data'] = preg_replace ('/\'/','\\\'', $single_message['data']);
					unset ($single_message['message_html']);
					$single_message['filename'] = $single_message['expiration'];
					unset ($single_message['expiration']);
					$single_message['svn_version'] = $single_message['message_id'];
					unset ($single_message['message_id']);
					
					// Add common tconfig id_update_package
					$single_message['id_update_package'] = $config['id_um_package_messages'];
					
					$result = db_process_sql_insert('tupdate', $single_message);
				}
			}
			break;			
		default:
			break;
	}	
}

function update_manager_remote_read_messages ($id_message) {
	global $config;
	
	$params = array(
		'pandora_uid' => $config['pandora_uid'],
		'message_id' => $id_message
		);
	
	$result = update_manager_curl_request ('mark_as_read', $params);
	
	return $result['success'];
}

function update_manager_extract_package() {
	global $config;
	
	$path_package = $config['attachment_store'] .
		"/downloads/last_package.tgz";

	ob_start();

	if (!defined('PHP_VERSION_ID')) {
		$version = explode('.', PHP_VERSION);
		define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
	}

	$extracted = false;

	// Phar and exception working fine in 5.5.0 or higher
	if (PHP_VERSION_ID >= 50505) {
		$phar = new PharData($path_package);
		try {
			$result = $phar->extractTo($config['attachment_store'] . "/downloads/",null, true);
			$extracted = true;
		}
		catch (Exception $e) {
			echo ' There\'s a problem ... -> ' . $e->getMessage();
			$extracted = false;
		}
	}
	$return = true;

	if($extracted === false) {
		$return = false;

		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
			// unsupported OS
			echo "This OS [" . PHP_OS . "] does not support direct extraction of tgz files. Upgrade PHP version to be > 5.5.0";
		}
		else {
			$return = true;
			system('tar xzf "' . $path_package . '" -C ' . $config['attachment_store'] . "/downloads/");
		}
	}

	ob_end_clean();

	rrmdir($path_package);

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

	return $return;
}

/**
 * The update copy entirire the tgz or fail (leave some parts copies and some part not).
 * This does make any thing with the BD.
 */
function update_manager_starting_update() {
	global $config;
	
	$full_path = $config['attachment_store'] . "/downloads";
	
	$homedir = $config['homedir'];

	$result = update_manager_recurse_copy(
		$full_path,
		$homedir,
		array('install.php'));

	rrmdir($full_path . "/pandora_console");

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

// Get the last message
function update_manger_get_last_message () {
	global $config;
	
	$sql = 'SELECT data, svn_version, db_field_value FROM tupdate ';
	$sql .= 'WHERE data_rollback NOT LIKE \'%"' . $config['id_user'] . '":1%\' ';
	$sql .= 'OR data_rollback IS NULL ';
	$sql .= 'ORDER BY svn_version DESC ';
	
	$message = db_get_row_sql($sql);
	return $message;
}

// Get the a single message message
function update_manger_get_single_message ($message_id) {
	global $config;
	
	$sql = 'SELECT data, svn_version, db_field_value FROM tupdate ';
	$sql .= 'WHERE svn_version=' . $message_id;
	
	$message = db_get_row_sql($sql);
	return $message;
}

function update_manager_get_total_messages () {	
	global $config;
	
	$sql = 'SELECT COUNT(*) FROM tupdate';
	return (int) db_get_sql ($sql);	
}

function update_manager_get_unread_messages () {
	global $config;
	
	$total = update_manager_get_total_messages ();
	$sql = 'SELECT COUNT(*) FROM tupdate WHERE data_rollback LIKE \'%"' . $config['id_user'] . '":1%\'';
	$read = (int) db_get_sql ($sql);
	
	return $total - $read;	
}

function update_manager_get_not_deleted_messages () {
	global $config;
	
	$total = update_manager_get_total_messages ();
	$sql = 'SELECT COUNT(*) FROM tupdate WHERE description LIKE \'%"' . $config['id_user'] . '":1%\'';
	$read = (int) db_get_sql ($sql);
	
	return $total - $read;	
}

function update_manger_set_deleted_message ($message_id) {
	global $config;
	
	$rollback = db_get_value('description', 'tupdate', 'svn_version', $message_id);
	$users_read = json_decode ($rollback, true);
	$users_read[$config['id_user']] = 1;
	
	$rollback = json_encode ($users_read);
	db_process_sql_update('tupdate', array('description' => $rollback, ), array('svn_version' => $message_id));	
	
	//Mark as read too
	update_manger_set_read_message ($message_id, 1);
}
?>
