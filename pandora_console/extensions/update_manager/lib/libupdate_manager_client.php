<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

$prev_level = error_reporting (0);
error_reporting ($prev_level);

include("xmlrpc/xmlrpc.inc");
include("xmlrpc/xmlrpcs.inc");
include("xmlrpc/xmlrpc_wrappers.inc");

unset ($prev_level);

define ('XMLRPC_DEBUG', 0);
define ('XMLRPC_TIMEOUT', 15);
global $config;
define('HOME_DIR', $config['homedir']);

function um_xml_rpc_client_call ($server_host, $server_path, $server_port, $proxy, $proxy_port, $proxy_user, $proxy_pass, $function, $parameters) {
	$msg = new xmlrpcmsg ($function, $parameters);
	
	$client = new xmlrpc_client($server_path, $server_host, $server_port);
	
	$client->setProxy($proxy, $proxy_port, $proxy_user, $proxy_pass);
	
	if (defined ('XMLRPC_DEBUG'))
		$client->setDebug (XMLRPC_DEBUG);
	
	$result = $client->send ($msg, XMLRPC_TIMEOUT, '');
	
	if (! $result) {
		trigger_error ('<strong>Open Update Manager</strong> Server comunication error. '.$client->errstr);
		return 0;
	}
	switch($result->faultCode ()) {
		case 0:
			break;
		case 5: 
			trigger_error ('<strong>Open Update Manager</strong> Server comunication error. '.$result->faultString ());
			return 0;
		default:
			trigger_error ('<strong>Open Update Manager</strong> XML-RPC error. '.$result->faultString ());
			return 0;
	}
	
	return $result;
}

function um_xml_rpc_unpack_update ($update_xml_rpc) {
	if ($update_xml_rpc->kindOf () != 'struct') {
		return false;
	}
	
	$update = new stdClass ();
	$value = $update_xml_rpc->structeach ();
	while ($value) {
		$update->$value['key'] = $value[1]->scalarval ();
		$value = $update_xml_rpc->structeach ();
	}
	
	return $update;
}

function um_xml_rpc_unpack_package ($package_xml_rpc) {
	if ($package_xml_rpc->kindOf () != 'struct') {
		return false;
	}
	
	$package = new stdClass ();
	$value = $package_xml_rpc->structeach ();
	while ($value) {
		$package->$value['key'] = $value[1]->scalarval ();
		$value = $package_xml_rpc->structeach ();
	}
	
	if (! isset ($package->updates))
		return $package;
	
	$package->updates = array ();
	$updates = $package_xml_rpc->structmem ('updates');
	$size = $updates->arraysize ();
	for ($i = 0; $i < $size; $i++) {
		$update = um_xml_rpc_unpack_update ($updates->arraymem ($i));
		$update->id_update_package = $package->id;
		array_push ($package->updates, $update);
	}
	
	return $package;
}

function um_client_check_latest_update ($settings, $user_key) {
	$params = array (new xmlrpcval ($settings->customer_key, 'string'),
		new xmlrpcval ($user_key, 'string'),
		new xmlrpcval ($settings->current_update, 'int'));
	
	$result = um_xml_rpc_client_call ($settings->update_server_host,
		$settings->update_server_path,
		$settings->update_server_port,
		$settings->proxy,
		$settings->proxy_port,
		$settings->proxy_user,
		$settings->proxy_pass,
		'get_latest_package', $params);
	
	if ($result == false) {
		return $result;
	}
	
	$value = $result->value ();
	
	if ($value->kindOf () == 'scalar') {
		/* No new updates */
		return $value->scalarval ();
	}
	$package = um_xml_rpc_unpack_package ($value);
	
	return $package;
}

function um_client_get_package ($settings, $user_key) {
	$params = array (new xmlrpcval ($settings->customer_key, 'string'),
		new xmlrpcval ($user_key, 'string'),
		new xmlrpcval ($settings->current_update, 'int'));
	
	$result = um_xml_rpc_client_call ($settings->update_server_host,
		$settings->update_server_path,
		$settings->update_server_port,
		$settings->proxy,
		$settings->proxy_port,
		$settings->proxy_user,
		$settings->proxy_pass,
		'get_next_package', $params);
	
	if ($result === false)
		return false;
	
	$value = $result->value ();
	if ($value->kindOf () == 'scalar') {
		/* No new updates */
		return (int)$value->scalarval ();
	}
	
	$package = um_xml_rpc_unpack_package ($value);
	
	return $package;
}

function um_client_db_save_package ($package, $settings) {
	$fields = array ('id' => $package->id,
		'description' => $package->description,
		'timestamp' => $package->timestamp);
	
	um_client_db_connect($settings);
	
	$result = db_process_sql_insert(DB_PREFIX.'tupdate_package', $fields);
	
	if($result === false) {
		return false;
	}
	
	return true;
}

function um_client_db_save_update ($update) {	
	$fields = get_object_vars ($update);
	
	$fields['data'] = base64_encode($fields['data']);
	
	if(isset($fields['data_rollback'])) {
		$fields['data_rollback'] = base64_encode($fields['data_rollback']);
	}

	if($fields['type'] == 'db_data') {
		$fields['db_table_name'] = $fields['db_table'];
		unset($fields['db_table']);
		$fields['db_field_name'] = $fields['db_field'];
		unset($fields['db_field']);
		unset($fields['order']);
	}
	
	$result = db_process_sql_insert(DB_PREFIX.'tupdate', $fields);
	
	if($result === false) {
		return false;
	}
	
	return true;
}

function um_client_apply_update_file (&$update, $destiny_filename, $force = true) {
	@mkdir (dirname ($destiny_filename), 0755, true);
	
	if (file_exists ($destiny_filename)) {
		if (! is_writable ($destiny_filename)) {
			return false;
		}
		$checksum = md5_file ($destiny_filename);
		if (! $force && $update->previous_checksum != '') {
			if ($update->previous_checksum != $checksum)
				/* Local changes in the file. Don't update */
				return false;
		}
		$content = file_get_contents ($destiny_filename);
		$update->data_rollback = um_data_encode ($content);
		$update->previous_checksum = $checksum;
	}
	$result = file_put_contents ($destiny_filename, um_data_decode ($update->data));
	if ($result === false) {
		return false;
	}
	return true;
}
function um_client_create_update_file ($data, $md5path_name) {
	@mkdir (dirname ($md5path_name), 0755, true);
	$result = file_put_contents ($md5path_name, um_data_decode ($data));
	if ($result === false) {
		return false;
	}
	return true;
}

function um_client_apply_update_database (&$update) {
	if ($update->type == 'db_data') {
		$exists = db_get_value('COUNT(*)', $update->db_table, $update->db_field, $update->db_field_value);
		
		/* If it exists, it failed. */
		if ($exists != 0) {
			return false;
		}
	}
	
	$query_array = explode(';',um_data_decode($update->data));
	$result = db_process_sql($query_array[0]);
	
	if ($result === false) {
		//echo $result->getMessage ();
		return false;
	}
	return true;
}

function um_client_apply_update (&$update, $settings, $force = true) {
	if ($update->type == 'code') {
		// We use the Pandora Home dir of config to code files
		$filename = HOME_DIR.'/'.$update->filename;
		$success = um_client_apply_update_file ($update, $filename, $force);
	}
	else if ($update->type == 'binary') {
		$filename = $settings->updating_binary_path.'/'.$update->filename;
		$success = um_client_apply_update_file ($update, $filename);
	}
	else if ($update->type == 'db_data' || $update->type == 'db_schema') {
		//mysql_select_db ($settings->dbname);
		//mysql_connect ($settings->dbhost, $settings->dbuser, $settings->dbpass);
		um_component_db_connect ();
		$success = um_client_apply_update_database ($update);
	}
	else {
		return false;
	}
	
	if (! $success)
		return false;
	return true;
}

function um_client_rollback_update_file (&$update, $destiny_filename) {
	/* If there's no data rollback, we suppose it's a new file, so it should 
		not be a problem. In any case, it's better than deleting the file. */
	if (! isset ($update->data_rollback))
		return true;
	
	$result = file_put_contents ($destiny_filename,
		um_data_decode ($update->data_rollback));
	
	if ($result === false)
		return false;
	
	return true;
}

function um_client_rollback_update (&$update, $settings) {
	if ($update->type == 'code') {
		$filename = $settings->updating_code_path.'/'.$update->filename;
		$success = um_client_rollback_update_file ($update, $filename);
	}
	else if ($update->type == 'binary') {
		$filename = $settings->updating_binary_path.'/'.$update->filename;
		$success = um_client_rollback_update_file ($update, $filename);
	}
	else if ($update->type == 'db_data' || $update->type == 'db_schema') {
		db_process_sql_rollback();
		$success = true;
	}
	else {
		return false;
	}
	
	return $success;
}

function um_client_crypt ($data) {
	// Customizable crypt code
	return $data;
}

function um_client_decrypt ($data) {
	// Customizable crypt code
	return $data;
}

///////////////////////////////////////////////
// Get a directory path and return an array  //
// with the path of all files into directory //
///////////////////////////////////////////////
function um_client_get_files ($dir_path) {
	if(!file_exists($dir_path)) {
		return array();
	}
	
	$dir = opendir($dir_path);
	$files = array();
	$cont = 0;
	while ($element = readdir($dir))
	{
		if($element == '.' || $element == '..') {
			continue;
		}
		if(is_dir($dir_path.$element)) {
			$file_temp = um_client_get_files ("$dir_path$element/");
			$files = array_merge((array)$files,(array)$file_temp);
		}
		else {
			$files[$element][] = $dir_path;
		}
		
		$cont++;
	}
	closedir($dir);
	
	return $files;
}

function um_client_print_update ($update, $settings) {
	if(isset($update->id)) {
		echo 'Update #'.$update->id;
	}
	
	echo '<ul>';
	echo '<li><em>Type</em>: '.$update->type.'</li>';
	if ($update->type == 'code' || $update->type == 'binary') {
		if ($update->type == 'code') {
			$realpath = HOME_DIR.'/'.$update->filename;
		}
		else {
			$realpath = $settings->updating_binary_path.'/'.$update->filename;
		}
		echo '<li><em>Filename</em>: '.$update->filename.'</li>';
		echo '<li><em>Realpath</em>: '.$realpath.'</li>';
		echo '<li><em>Checksum</em>: '.$update->checksum.'</li>';
		echo '<li><em>Writable</em>: '.(is_writable ($realpath) ? 'yes' : '<strong>no</strong>').'</li>';
	}
	else {
		if(isset($update->db_table))
			echo '<li><em>Table</em>: '.$update->db_table.'</li>';
		if(isset($update->db_field))
			echo '<li><em>Field</em>: '.$update->db_field.'</li>';
		if(isset($update->db_field_value))
			echo '<li><em>Value</em>: '.$update->db_field_value.'</li>';
		echo '<li><em>Data</em>: '.um_data_decode($update->data).'</li>';
	}
	echo '</ul>';
}

function um_package_info_from_paths ($tmpDir) {
	$f = @fopen($tmpDir.'info_package', "r");
	
	if ($f !== false) {
		$f_content = fread($f, filesize($tmpDir.'info_package'));
		fclose($f);
		$f_array = array();
		
		unset($f_content->status);
		
		return json_decode($f_content);
	}
	else {
		return false;
	}
}

function um_client_update_from_paths ($file_paths, $tmpDir, $num_package, $type) {
	global $config;
	
	$update = array();
	$i = 0;
	
	// The number of the prefixs names is to keep alphabetic order to appliyng priority
	switch ($config["dbtype"]) {
		case "mysql":
			$sql_schema_file = '01_package_'.$num_package.'_schema.sql';
			$sql_data_file = '02_package_'.$num_package.'_data.sql';
			break;
		case "postgresql":
			$sql_schema_file = '01_package_'.$num_package.'_schema.postgreSQL.sql';
			$sql_data_file = '02_package_'.$num_package.'_data.postgreSQL.sql';
			break;
		case "oracle":
			$sql_schema_file = '01_package_'.$num_package.'_schema.oracle.sql';
			$sql_data_file = '02_package_'.$num_package.'_data.oracle.sql';
			break;
	}
	
	foreach($file_paths as $file_name => $paths) {
		if($file_name == $sql_data_file && $type == 'sql') {
			$filesize = filesize($tmpDir.$sql_data_file);
			
			if($filesize == 0) {
				continue;
			}
			
			$f = fopen($tmpDir.$sql_data_file, "r");
			$f_content = fread($f, $filesize);
			fclose($f);
			$f_array = array();
			
			$settings = um_db_load_settings ();
			
			$f_array = explode(chr(10).chr(10),$f_content);
			foreach($f_array as $f_line) {
				if($f_line != '') {
					$segments = explode(";", $f_line);
					$update[$i]->data = um_data_encode($segments[0]);
					$update[$i]->db_table = $segments[1];
					$update[$i]->db_field = $segments[2];
					$update[$i]->db_field_value = $segments[3];
					$update[$i]->type = 'db_data';
					$update[$i]->id_update_package = $num_package;
					$i++;
				}
			}
		}
		elseif($file_name == $sql_schema_file && $type == 'sql') {
			$f = fopen($tmpDir.$sql_schema_file, "r");
			$f_content = fread($f, filesize($tmpDir.$sql_schema_file));
			fclose($f);
			$f_array = array();
			
			$f_array = explode(chr(10).chr(10),$f_content);
			foreach($f_array as $f_line) {
				if($f_line != '') {
					$segments = explode(";", $f_line);
					$update[$i]->data = um_data_encode($segments[0]);
					$update[$i]->id = $segments[1];
					$update[$i]->type = 'db_schema';
					$update[$i]->id_update_package = $num_package;
					$i++;
				}
			}
		}
		else {
			foreach($paths as $path) {
				$f = fopen($tmpDir.$type.preg_replace('/\s/','',$path).$file_name, "r");
				$f_content = fread($f, filesize($tmpDir.$type.preg_replace('/\s/','',$path).$file_name));
				fclose($f);
				// Delete the first character "/"
				$path = substr($path,2);
				
				$update[$i]->filename = preg_replace('/\s/','',$path).$file_name;
				//$f_content = file_get_contents($tmpDir.$type.preg_replace('/\s/','',$path).$file_name);
				$data = um_data_encode($f_content);
				
				$update[$i]->data = $data;
				$update[$i]->type = $type;
				$update[$i]->previous_checksum = '';
				$update[$i]->checksum = md5($f_content);
				$update[$i]->id_update_package = $num_package;
				$i++;
			}
		}
	}
	return $update;
}

function um_client_upgrade_to_package ($package, $settings, $force = true, $update_offline = false) {
	$applied_updates = array ();
	$rollback = false;
	
	
	if (!is_object($package)) {
		return false;
	}
	
	if (!$update_offline) {
		um_client_db_connect ($settings);
		um_component_db_connect ();
		foreach ($package->updates as $update) {
			$success = um_client_apply_update ($update, $settings, $force);	
			if (! $success) {
				echo '<p /><strong>Failed</strong> on:<br />';
				um_client_print_update ($update, $settings);
				$rollback = true;
				break;
			}
			
			array_push ($applied_updates, $update);
		}
		
		if ($rollback) {
			foreach ($applied_updates as $update) {
				$success = um_client_rollback_update ($update, $settings);
			}
			return false;
		}
		
		um_client_db_save_package ($package, $settings);
		
		foreach ($package->updates as $update) {
			um_client_db_save_update ($update);
		}
		
		um_db_update_setting ('current_update', $package->id);
		
		db_process_sql_commit();
	}
	else {
		$data_queries = '';
		$schema_queries = '';
		$zip = new ZipArchive;
		$temp_files = array();
		$md5_dir = md5("update_".$package->id);
		
		// Get the temp path on the server
		$path_script = explode('/',$_SERVER['SCRIPT_FILENAME']);
		$path_server = explode('/',$_SERVER['DOCUMENT_ROOT']);
		
		unset($path_script[count($path_script)-1]);
		
		$tempDir = implode('/',$path_script).'/temp/';
		
		for($i=0; $i<count($path_server); $i++) {
			unset($path_script[$i]);
		}
		
		$tempDirServer = '/'.implode('/',$path_script).'/temp/';
				
		$package_name = 'package_'.$package->id.'.oum';
		$zipArchive = $tempDir . $package_name;
		$zipArchiveServer = $tempDirServer . $package_name;
		
		@unlink($zipArchive);
		
		if ($zip->open($zipArchive, ZIPARCHIVE::CREATE) === true) {
			foreach ($package->updates as $update) {
				$filename = '';
				
				switch($update->type) {
					case 'code':
					case 'binary':
						$md5_name = md5($path_remote.$update->filename);
						$success = um_client_create_update_file ($update->data, $tempDir.$md5_name);
						$zip->addFile($tempDir.$md5_name, $update->type.'/'.$update->filename);
						$temp_files[] = $tempDir.$md5_name;
						break;
					case 'db_data':
						$data = um_data_decode($update->data);
						if(substr($data, strlen($data)-1,1) != ';') {
							$data_queries .= $data.";".$update->db_table.";".$update->db_field.";".$update->db_field_value.";".$update->id.";\n\n";
						}
						else {
							$data_queries .= $data.$update->db_table.";".$update->db_field.";".$update->db_field_value.";".$update->id.";\n\n";
						}
						break;
					case 'db_schema':
						$data = um_data_decode($update->data);
						if(substr($data, strlen($data)-1,1) != ';') {
							$schema_queries .= $data.";".$update->id.";\n\n";
						}
						else {
							$schema_queries .= $data.$update->id.";\n\n";
						}
						break;
				}
			}
			// Creating the schema sql script
			$success = um_client_create_update_file (um_data_encode($schema_queries), $tempDir."schema_db");
			$zip->addFile($tempDir."schema_db", '01_package_'.$package->id.'_schema.sql');
			$temp_files[] = $tempDir."schema_db";
			
			// Creating the data sql script
			$success = um_client_create_update_file (um_data_encode($data_queries), $tempDir."data_db");
			$zip->addFile($tempDir."data_db", '02_package_'.$package->id.'_data.sql');
			$temp_files[] = $tempDir."data_db";
			
			// Creating the package info file
			$package_info = $package;
			unset($package_info->updates);
			
			$success = um_client_create_update_file (um_data_encode(json_encode($package_info)), $tempDir."info_package");
			$zip->addFile($tempDir."info_package", 'info_package');
			$temp_files[] = $tempDir."info_package";
			
			$zip->close();
			
			// Clean temp files
			foreach($temp_files as $file) {
				@unlink($file);
			}
			
			chdir($tempDir);
			
			header("Content-type: application/zip");
			header("Content-Disposition: attachment; filename=" 
				. $package_name);
			header("Pragma: no-cache");
			header("Expires: 0");
			readfile($package_name);
			
			return true;
		}
	}
	
	return true;
}

function um_client_upgrade_to_latest ($user_key, $force = true) {
	$success = false;
	
	$settings = um_db_load_settings ();
	db_process_sql_begin();
	do {
		$package = um_client_get_package ($settings, $user_key);
		
		if ($package === false || $package === true ||
			$package === 0 || $package === 1) {
			break;
		}
		
		$success = um_client_upgrade_to_package ($package, $settings, $force);
		
		if (! $success)
			break;
		
		$settings->current_update = $package->id;
	}
	while (1);
	
	/* Break on error, when there are no more packages on the server (server return true)
		or on auth failure (server return false) */
	
	return $success;
}

function um_client_db_connect (&$settings = NULL) {
	if (! $settings)
		$settings = um_db_load_settings ();
	
	//mysql_select_db (DB_NAME);
	mysql_connect ($settings->dbhost . ':' . $settings->dbport, $settings->dbuser,
		$settings->dbpass);
}
?>
