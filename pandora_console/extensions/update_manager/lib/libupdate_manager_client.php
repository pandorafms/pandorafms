<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


$prev_level = error_reporting (0);
if ((include_once ('XML/RPC.php')) != 1)
	die ('<p>PEAR::XML_RPC not found. Please install it with: <pre>pear install XML_RPC</pre></p>');
error_reporting ($prev_level);
unset ($prev_level);

define ('XMLRPC_DEBUG', 0);
define ('XMLRPC_TIMEOUT', 15);

function um_xml_rpc_client_call ($server_host, $server_path, $server_port, $proxy, $proxy_port, $proxy_user, $proxy_pass, $function, $parameters) {
	$msg = new XML_RPC_Message ($function, $parameters);
	$client = new XML_RPC_Client ($server_path, $server_host, $server_port, $proxy, $proxy_port, $proxy_user, $proxy_pass);
	if (defined ('XMLRPC_DEBUG'))
		$client->setDebug (XMLRPC_DEBUG);
	$result = $client->send ($msg, XMLRPC_TIMEOUT);
	
	if (! $result) {
		echo '<strong>Open Update Manager</strong> Server comunication error: '.$client->errstr;
		return false;
	}
	if ($result->faultCode ()) {
		echo '<strong>Open Update Manager</strong> XML-RPC error: '.$result->faultString ();
		return false;
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
	$params = array (new XML_RPC_Value ($settings->customer_key, 'string'),
				new XML_RPC_Value ($user_key, 'string'),
				new XML_RPC_Value ($settings->current_update, 'int'));
	$result = um_xml_rpc_client_call ($settings->update_server_host,
			$settings->update_server_path,
			$settings->update_server_port,
			$settings->proxy,
			$settings->proxy_port,
			$settings->proxy_user,
			$settings->proxy_pass,
			'get_latest_package', $params);
	
	if ($result === false) {
		return false;
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
	$params = array (new XML_RPC_Value ($settings->customer_key, 'string'),
				new XML_RPC_Value ($user_key, 'string'),
				new XML_RPC_Value ($settings->current_update, 'int'));
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
		return (bool) $value->scalarval ();
	}
	
	$package = um_xml_rpc_unpack_package ($value);
	
	return $package;
}

function um_client_db_save_package ($package) {
	global $db;
	
	$fields = array ('id' => $package->id,
			'description' => $package->description);
	$replace = array ();
	for ($i = 0; $i < sizeof ($fields); $i++) {
		$replace[] = '?';
	}
	
	$sql =& $db->prepare ('INSERT INTO tupdate_package ('.implode(',', array_keys ($fields)).') VALUES ('.implode(',', $replace).')');
	$result =& $db->execute ($sql, $fields);
	if (PEAR::isError ($result)) {
		return false;
	}
	return true;
}

function um_client_db_save_update ($update) {
	global $db;
	
	$fields = array_keys (get_object_vars ($update));
	$values = array_values (get_object_vars ($update));
	$replace = array ();
	for ($i = 0; $i < sizeof ($values); $i++) {
		$replace[] = '?';
	}
	
	$sql =& $db->prepare ('INSERT INTO tupdate ('.implode(',', $fields).') VALUES ('.implode(',', $replace).')');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		return false;
	}
	return true;
}

function um_client_apply_update_file (&$update, $destiny_filename, $force = false) {
	@mkdir (dirname ($destiny_filename), 0777, true);
	
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
		$update->data_rollback = convert_uuencode ($content);
		$update->previous_checksum = $checksum;
	}
	$result = file_put_contents ($destiny_filename, convert_uudecode ($update->data));
	if ($result === false) {
		return false;
	}
	return true;
}

function um_client_apply_update_database (&$update, &$db) {
	if ($update->type == 'db_data') {
		$values = array ($update->db_table,
						$update->db_field,
						$update->db_field_value);
		$exists =& $db->getOne ('SELECT COUNT(*) FROM `!` WHERE ? = ?', $values);
		if (PEAR::isError ($exists)) {
			return false;
		}
		/* If it exists, it failed. */
		if ($exists)
			return false;
	}
	
	$result =& $db->query ($update->data);
	if (PEAR::isError ($result)) {
		echo $result->getMessage ();
		return false;
	}
	return true;
}

function um_client_apply_update (&$update, $settings, $db, $force = false) {
	if ($update->type == 'code') {
		$filename = $settings->updating_code_path.'/'.$update->filename;
		$success = um_client_apply_update_file ($update, $filename, $force);
	} else if ($update->type == 'binary') {
		$filename = $settings->updating_binary_path.'/'.$update->filename;
		$success = um_client_apply_update_file ($update, $filename);
	} else if ($update->type == 'db_data' || $update->type == 'db_schema') {
		$success = um_client_apply_update_database ($update, $db);
	} else {
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
	
	$result = file_put_contents ($destiny_filename, convert_uudecode ($update->data_rollback));
	
	if ($result === false)
		return false;
	return true;
}

function um_client_rollback_update (&$update, $settings, $db) {
	if ($update->type == 'code') {
		$filename = $settings->updating_code_path.'/'.$update->filename;
		$success = um_client_rollback_update_file ($update, $filename);
	} else if ($update->type == 'binary') {
		$filename = $settings->updating_binary_path.'/'.$update->filename;
		$success = um_client_rollback_update_file ($update, $filename);
	} else if ($update->type == 'db_data' || $update->type == 'db_schema') {
		$db->rollback ();
		$success = true;
	} else {
		return false;
	}
	
	return $success;
}

function um_client_print_update ($update, $settings) {
	echo 'Update #'.$update->id;
	echo '<ul>';
	echo '<li><em>Type</em>: '.$update->type.'</li>';
	if ($update->type == 'code' || $update->type == 'binary') {
		if ($update->type == 'code') {
			$realpath = $settings->updating_code_path.'/'.$update->filename;
		} else {
			$realpath = $settings->updating_binary_path.'/'.$update->filename;
		}
		echo '<li><em>Filename</em>: '.$update->filename.'</li>';
		echo '<li><em>Realpath</em>: '.$realpath.'</li>';
		echo '<li><em>Checksum</em>: '.$update->checksum.'</li>';
		echo '<li><em>Writable</em>: '.(is_writable ($realpath) ? 'yes' : '<strong>no</strong>').'</li>';
	} else {
		echo '<li><em>Table</em>: '.$update->db_table.'</li>';
		echo '<li><em>Field</em>: '.$update->db_field.'</li>';
		echo '<li><em>Value</em>: '.$update->db_field_value.'</li>';
		echo '<li><em>Data</em>: '.$update->data.'</li>';
	}
	echo '</ul>';
}

function um_client_upgrade_to_package ($package, $settings, $force = false) {
	$applied_updates = array ();
	$rollback = false;
	
	$db = um_client_db_connect ($settings);
	if ($db === false)
		return false;
	foreach ($package->updates as $update) {
		$success = um_client_apply_update ($update, $settings, $db, $force);
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
			$success = um_client_rollback_update ($update, $settings, $db);
		}
		return false;
	}
	$db->commit ();
	um_client_db_save_package ($package);
	foreach ($package->updates as $update) {
		um_client_db_save_update ($update);
	}
	
	um_db_update_setting ('current_update', $package->id);
	return true;
}

function um_client_upgrade_to_latest ($user_key, $force) {
	$settings = um_db_load_settings ();
	do {
		$package = um_client_get_package ($settings, $user_key);
		if ($package === false || $package === true)
			break;
		$success = um_client_upgrade_to_package ($package, $settings, $force);
		if (! $success)
			break;
		
		
		$settings->current_update = $package->id;
	} while (1);
	/* Break on error, when there are no more packages on the server (server return true)
		or on auth failure (server return false) */
}

function um_client_db_connect (&$settings = NULL) {
	if (! $settings)
		$settings = um_db_load_settings ();
	
	$dsn = 'mysql://'.$settings->dbuser.':'.$settings->dbpass.'@'.$settings->dbhost.'/'.$settings->dbname;
	$db =& DB::Connect ($dsn, array ());
	if (PEAR::isError ($db)) {
		return false;
	}
	$db->setFetchMode (DB_FETCHMODE_ASSOC);
	$db->autoCommit (false);
	
	return $db;
}
?>
