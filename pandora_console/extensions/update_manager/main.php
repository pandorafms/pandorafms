<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
global $config;

require_once("update_pandora.php");

check_login ();

um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
	$config['dbpass'], $config['dbname']);

$settings = um_db_load_settings ();
$error = '';

$url = 'index.php?sec=extensions&sec2=extensions/update_manager';

$update_free = (bool)get_parameter('update_free', false);

$buttons_active = array();
if ($update_free) {
	$buttons_active['updatemanager'] = false;
	$buttons_active['updatefree'] = true;
}
else {
	$buttons_active['updatemanager'] = true;
	$buttons_active['updatefree'] = false;
}

$buttons = array(
	'updatefree' => array('active' => $buttons_active['updatefree'],
			'text' => '<a href="'.$url.'&amp;update_free=1">' . 
				html_print_image("images/upd.png", true, array ("title" => __('Update free'))) .'</a>'),
	'updatemanager' => array('active' => $buttons_active['updatemanager'],
			'text' => '<a href="'.$url.'&amp;update_manager=1">' . 
				html_print_image("images/package.png", true, array ("title" => __('Update manager'))) .'</a>'));


ui_print_page_header (__('Update manager'), "images/extensions.png",
	false, "", false, $buttons);

if ($update_free) {
	//Call to update pandora free.
	
	update_pandora_administration();
	
	//Don't call the code of update manager.
	return;
}


if ($settings->customer_key == FREE_USER) {
	echo '<div class="notify" style="width: 80%; text-align:left;" >';
	echo html_print_image("images/information.png", true) . '&nbsp;'; 
	/* Translators: Do not translade Update Manager, it's the name of the program */
	echo __('The new <a href="http://updatemanager.sourceforge.net">Update Manager</a> client is shipped with Pandora FMS It helps system administrators to update their Pandora FMS automatically, since the Update Manager does the task of getting new modules, new plugins and new features (even full migrations tools for future versions) automatically.');
	echo '<p />';
	echo __('Update Manager is one of the most advanced features of Pandora FMS Enterprise version, for more information visit <a href="http://pandorafms.com">http://pandorafms.com</a>.');
	echo '<p />';
	echo __('Update Manager sends anonymous information about Pandora FMS usage (number of agents and modules running). To disable it, just delete extension or remove remote server address from Update Manager plugin setup.');
	echo '</div>';
}

$user_key = get_user_key ($settings);
$update_package = (bool) get_parameter_post ('update_package');
	
if ($update_package) {
	if ($config['enterprise_installed'] == 1) {
		echo '<h2>'.__('Updating').'...</h2>';
		flush ();
		$force = (bool) get_parameter_post ('force_update');
		
		um_client_upgrade_to_latest ($user_key, $force);
		/* TODO: Add a new in tnews */
	
	}
	else {
		echo '<h5 class="error">' . __('This is an Enterprise feature. Visit %s for more information.', '<a href="http://pandorafms.com">http://pandorafms.com</a>') . '</h5>';
	}
}

if (isset($_FILES["fileloaded"]["error"]) && !$_FILES["fileloaded"]["error"]) {
	$extension = substr($_FILES["fileloaded"]["name"], strlen($_FILES["fileloaded"]["name"])-4, 4);
	if($extension != '.oum') {
		$error = '<h5 class="error">'.__('Incorrect file extension').'</h5>';
	}
	else {
		$tempDir = sys_get_temp_dir()."/tmp_oum/";
		
		$zip = new ZipArchive;
		if ($zip->open($_FILES["fileloaded"]['tmp_name']) === TRUE) {
			$zip->extractTo($tempDir);
			$zip->close();
		} else {
			$error = '<h5 class="error">'.__('Update cannot be opened').'</h5>';
		}
		
		$package = um_package_info_from_paths ($tempDir);
		if ($package === false) {
			$error = '<h5 class="error">'.__('Error, the file package is empty or corrupted.').'</h5>';
		}
		else {
			$settings = um_db_load_settings ();
			
			if($settings->current_update >= $package->id) {
				$error = '<h5 class="error">'.__('Your system version is higher or equal than the loaded package').'</h5>';
			}
			else {
				$binary_paths = um_client_get_files ($tempDir."binary/");
				
				foreach($binary_paths as $key => $paths) {
					foreach($paths as $index => $path) {
						$tempDir_scaped = preg_replace('/\//', '\/', $tempDir."binary");
						$binary_paths[$key][$index] = preg_replace('/^'.$tempDir_scaped.'/', ' ', $path);
					}
				}
				
				$code_paths = um_client_get_files ($tempDir."code/");
				
				foreach($code_paths as $key => $paths) {
					foreach($paths as $index => $path) {
						$tempDir_scaped = preg_replace('/\//', '\/', $tempDir."code");
						$code_paths[$key][$index] = preg_replace('/^'.$tempDir_scaped.'/', ' ', $path);
					}
				}
				
				$sql_paths = um_client_get_files ($tempDir);
				foreach($sql_paths as $key => $paths) {
					foreach($paths as $index => $path) {
						if($path != $tempDir || ($key == 'info_package' && $path == $tempDir)) {
							unset($sql_paths[$key]);
						}
					}
				}
				
				$updates_binary = array();
				$updates_code = array();
				$updates_sql = array();
				
				if(!empty($binary_paths)) {
					$updates_binary = um_client_update_from_paths ($binary_paths, $tempDir, $package->id, 'binary');
				}
				if(!empty($code_paths)) {
					$updates_code = um_client_update_from_paths ($code_paths, $tempDir, $package->id, 'code');
				}
				if(!empty($sql_paths)) {
					$updates_sql = um_client_update_from_paths ($sql_paths, $tempDir, $package->id, 'sql');
				}
				
				um_delete_directory($tempDir);
				
				$updates= array_merge((array) $updates_binary, (array) $updates_code, (array) $updates_sql);
				
				$package->updates = $updates;
				
				$settings = um_db_load_settings ();
				
				if(um_client_upgrade_to_package ($package, $settings, true)) {
					echo '<h5 class="suc">'.__('Successfully upgraded').'.</h5>';
					$settings = um_db_load_settings ();
				}
				else {
					echo '<h5 class="error">'.__('Cannot be upgraded').'</h5>';
				}
			}
		}
	}
}
else {
	$error = '<h5 class="error">'.__('File cannot be uploaded').'</h5>';
}

db_clean_cache();
$settings = um_db_load_settings ();
$user_key = get_user_key ($settings);

$package = um_client_check_latest_update ($settings, $user_key);

if (check_acl ($config['id_user'], 0, 'PM')) {
	if ($package === true) {
		echo '<h5 class="suc">'.__('Your system is up-to-date').'.</h5>';
	}
	elseif ($package === false) {
		echo '<h5 class="error">'.__('Server authorization rejected').'</h5>';
	}
	elseif ($package === 0) {
		echo '<h5 class="error">'.__('Server connection failed').'</h5>';
	}
	else {
		echo '<h5 class="suc">'.__('There\'s a new update for Pandora FMS').'</h5>';
		
		$table->width = '98%';
		$table->data = array ();
		
		$table->data[0][0] = '<strong>'.__('Id').'</strong>';
		$table->data[0][1] = $package->id;
	
		$table->data[1][0] = '<strong>'.__('Timestamp').'</strong>';
		$table->data[1][1] = $package->timestamp;
	
		$table->data[2][0] = '<strong>'.__('Description').'</strong>';
		$table->data[2][1] = html_entity_decode ($package->description);
	
		html_print_table ($table);
		echo '<div class="action-buttons" style="width: '.$table->width.'">';
		echo '<form method="post">';
		echo __('Overwrite local changes');
		html_print_checkbox ('force_update', '1', false);
		echo '<p />';
		html_print_input_hidden ('update_package', 1);
		html_print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
		echo '</form>';
		echo '</div>';
	}
	
	if($error != '' && isset($_FILES["fileloaded"]["error"])) {
		echo $error;
	}
	
	unset($table);
	
	$table->width = '98%';
	$table->data = array ();
	$table->colspan[0][0] = 2;

	$table->data[0][0] = '<h4>'.__('Offline packages loader').'</h4>';
	$table->data[1][0] = '<input type="hidden" name="upload_package" value="1">';
	$table->data[1][0] .= '<input type="file" size="55" name="fileloaded">';
	$table->data[1][1] = '<input type="submit" name="upload_button" value="'.__('Upload').'">';
	
	echo '<form method="post" enctype="multipart/form-data">';
	html_print_table($table);
	echo '</form>';

} 

echo '<h4>'.__('Your system version number is').': '.$settings->current_update.'</h4>';

?>
