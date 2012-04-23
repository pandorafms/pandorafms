<?php
//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

if (! defined ('DB_PREFIX'))
	define ('DB_PREFIX', '');

require_once ('libupdate_manager_utils.php');
require_once ('libupdate_manager_updates.php');
require_once ('libupdate_manager_components.php');
require_once ('libupdate_manager_client.php');

function um_db_load_settings () {
	db_clean_cache();
	$result = db_get_all_rows_in_table(DB_PREFIX . 'tupdate_settings');
	
	if ($result === false) {
		echo '<strong>Error reading settings</strong><br />';
		return NULL;
	}
	
	$settings = new stdClass ();
	$settings->proxy = '';
	$settings->proxy_port = '';
	$settings->proxy_user = '';
	$settings->proxy_pass = '';
	foreach($result as $field) {
		$settings->$field['key'] = $field['value'];
	}
	
	return $settings;
}

function um_db_update_setting ($key, $value = '') {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_get_value('COUNT(*)', DB_PREFIX.'tupdate_settings', '`key`', $key);
			break;
		case "postgresql":
			$result = db_get_value('COUNT(*)', DB_PREFIX.'tupdate_settings', '"key"', $key);
			break;
		case "oracle":
			$result = db_get_value('COUNT(*)', DB_PREFIX.'tupdate_settings', 'key', $key);
			break;
	}
	
	if ($result === false) {
		echo '<strong>' . __('Error reading settings') . '</strong> <br />';
		return NULL;
	}
	
	if ($result > 0) {
		switch ($config["dbtype"]) {
			case "mysql":
				$result = db_process_sql_update(DB_PREFIX.'tupdate_settings', array('value' => $value), array('`key`' => $key));
				break;
			case "postgresql":
				$result = db_process_sql_update(DB_PREFIX.'tupdate_settings', array('value' => $value), array('"key"' => $key));
				break;
			case "oracle":
				$result = db_process_sql_update(DB_PREFIX.'tupdate_settings', array('value' => $value), array('key' => $key));
				break;
		}
		
		if ($result === false) {
			echo '<strong>' . __('Error updating settings') . '</strong> <br />';
			return false;
		}
	}
	else {
		switch ($config["dbtype"]) {
			case "mysql":
				$result = db_process_sql_insert(DB_PREFIX.'tupdate_settings', array('`key`' => $key, '`value`' => $value));
				break;
			case "postgresql":
				$result = db_process_sql_insert(DB_PREFIX.'tupdate_settings', array('"key"' => $key, '"value"' => $value));
				break;
			case "oracle":
				$result = db_process_sql_insert(DB_PREFIX.'tupdate_settings', array('key' => $key, 'value' => $value));
				break;
		}
		
		if ($result === false) {
			echo '<strong>' . __('Error creating settings') . '</strong> <br />';
			return false;
		}
	}
	
	return true;
}

function um_db_get_latest_package_by_status ($id_package = '0', $status = 'public') {
	$result = db_process_sql('SELECT COUNT(*)
		FROM '.DB_PREFIX.'tupdate_package
		WHERE status = "'.$status.'" AND id > ' . $id_package . '
		ORDER BY id DESC LIMIT 1');
	
	if($result === false) {
		echo '<strong>Error reading latest package with status ' . $status . '</strong><br />';
		return false;
	}
	
	$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate_package WHERE status = "'.$status.'" AND id > ' . $id_package . ' ORDER BY id DESC LIMIT 1');
	
	$package = um_std_from_result($result);
	
	return $package;
}

function um_db_get_next_package ($id_package = '0', $development = false) {
	$package = um_db_get_latest_package_by_status ($id_package, $status = 'public');
	
	if (! $package && $development) {
		$package = um_db_get_latest_package_by_status ($id_package, $status = 'development');
	}
	
	return $package;
}

function um_db_create_package ($description = '') {
	$result = db_process_sql_insert(DB_PREFIX.'tupdate_package', array('description' => $description));
	
	if($result === false) {
		echo '<strong>Error creating package</strong><br />';
		return false;
	}
	
	return true;
}

function um_db_update_package ($id_package, $description = '', $status = 'disabled') {
	$values = array ('description' => $description, 'status' => $status);
	$where = array ('id' => $id_package);
	
	$result = db_process_sql_update(DB_PREFIX.'tupdate_package', $values, $where);
	
	if($result === false) {
		echo '<strong>Error updating package</strong><br />';
		return false;
	}
	
	return true;
}

function um_db_delete_package ($id_package) {
	$package = um_db_get_package ($id_package);
	
	if ($package->status != 'development') {
		echo '<strong>Error</strong>: '.'Only packages in development state can be deleted';
		return false;
	}
	
	$result = db_process_sql_delete(DB_PREFIX.'tupdate_package', array('id' => $id_package));
	
	if($result === false) {
		echo '<strong>Error deleting package</strong><br />';
		return false;
	}
	
	return true;
}

function um_db_get_package ($id_package) {	
	$result = db_process_sql ('SELECT * FROM '.DB_PREFIX.'tupdate_package WHERE id = ' . $id_package . ' LIMIT 1');
	if ($result === false) {
		echo '<strong>Error getting package info</strong><br />';
		return NULL;
	}
	
	$package = um_std_from_result($result);
	
	return $package;
}

function um_std_from_result($array, $i = 0) {
	if(!isset($array[$i])) {
		return false;
	}
	
	$object = new stdClass ();
	foreach($array[$i] as $key => $value) {
		if(!is_int($key)) {
			$object->$key = $value;
		}
	}
	
	return $object;
}

function um_db_get_all_packages () {
	$result = db_process_sql ('SELECT * FROM '.DB_PREFIX.'tupdate_package');
	if ($result === false) {
		echo '<strong>Error getting all packages</strong><br />';
		return NULL;
	}
	
	$cont = 0;
	$packages = array();
	while(true) {
		$package = um_std_from_result($result, $cont);
		if($package === false) {
			break;
		}
		$packages[$package->id] = $package;
		$cont++;
	}
	
	return $packages;
}

function um_db_get_package_updates ($id_package) {
	$result = db_process_sql ('SELECT COUNT(*) FROM '.DB_PREFIX.'tupdate WHERE id_update_package = ' . $id_package);
	if ($result === false) {
		echo '<strong>Error getting all packages '.$id_package.'</strong><br />'.'SELECT * FROM '.DB_PREFIX.'tupdate WHERE id_update_package = ' . $id_package;
		return NULL;
	}
	
	$result = db_process_sql ('SELECT * FROM '.DB_PREFIX.'tupdate WHERE id_update_package = ' . $id_package);
	
	$cont = 0;
	$updates = array();
	while(true) {
		$update = um_std_from_result($result, $cont);
		if($update === false) {
			break;
		}
		$component_db = um_db_get_component_db ($update->id_component_db);
		$update->db_table = $component_db->table_name;
		$update->db_field = $component_db->field_name;
		$update->order = $component_db->order;
		$updates[$update->id] = $update;
		$cont++;
	}
	
	return $updates;
}

function um_db_create_package_log ($id_package, $client_key, $user_package, $result = 'query', $user_subscription = '', $description = '') {
	global $db;
	
	$values = array ('id_update_package' => $id_package, 
		'client_key' => $client_key, 
		'ip_address' => $_SERVER['REMOTE_ADDR'], 
		'user_package' => $user_package, 
		'user_subscription' => $user_subscription, 
		'result' => $result, 
		'description' => $description);
	
	$result = db_process_sql_insert (DB_PREFIX.'tupdate_package_log', $values);
	
	if ($result === false) {
		return false;
	}
	
	return true;
}

function um_db_get_total_package_logs ($ip = '') {
	$result = db_process_sql('SELECT COUNT(*) total
		FROM '.DB_PREFIX.'tupdate_package_log
		WHERE ip_address LIKE "%'.$ip.'%"');
	
	if ($result === false) {
		echo '<strong>Error reading package log</strong> <br />';
		return 0;
	}
	
	$logs = um_std_from_result($result);
	
	return $logs->total;
}

function um_db_get_all_package_logs ($ip = '', $order_by = 'timestamp', $limit = 30, $offset = 0) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT COUNT(*)
				FROM '.DB_PREFIX.'tupdate_package_log
				WHERE ip_address LIKE "%'.$ip.'%"
				ORDER BY '.$order_by.' DESC LIMIT '.$limit.' OFFSET '.$offset);
			break;
		case "postgresql":
			$result = db_process_sql('SELECT COUNT(*)
				FROM '.DB_PREFIX.'tupdate_package_log
				WHERE ip_address LIKE \'%'.$ip.'%\'
				ORDER BY '.$order_by.' DESC LIMIT '.$limit.' OFFSET '.$offset);
			break;
		case "oracle":
			$set = array ();
			$set['ip_address'] = '%' . $ip . '%';
			$set['order'] = $order_by . ' DESC';
			$set['limit'] = $limit;
			$set['offset'] = $offset;
			$result = db_get_num_rows(oracle_recode_query ('SELECT * FROM '.DB_PREFIX.'tupdate_package_log WHERE', $set, 'AND', true));
			break;
	}
	
	
	if ($result === false) {
		echo '<strong>Error reading all package logs</strong> <br />';
		return array();
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate_package_log WHERE ip_address LIKE "%'.$ip.'%" ORDER BY '.$order_by.' DESC LIMIT '.$limit.' OFFSET '.$offset);
			break;
		case "postgresql":
			$result = db_process_sql('SELECT *
				FROM '.DB_PREFIX.'tupdate_package_log
				WHERE ip_address LIKE \'%'.$ip.'%\' ORDER BY '.$order_by.' DESC LIMIT '.$limit.' OFFSET '.$offset);
			break;
		case "oracle":
			$result = oracle_recode_query ('SELECT * FROM '.DB_PREFIX.'tupdate_package_log WHERE', $set, 'AND', false);
			// Delete rnum row generated by oracle_recode_query() function
			for ($i=0; $i < count($result); $i++) {
				unset($result[$i]['rnum']);		
			}
			break;
	}
	
	$cont = 0;
	$logs = array();
	while (true) {
		$log = um_std_from_result($result, $cont);
		if($log === false) {
			break;
		}
		$logs[$log->id] = $log;
		$cont++;
	}
	
	return $logs;
}

function um_db_delete_package_logs ($ip) {
	$result = db_process_sql_delete(DB_PREFIX.'tupdate_package_log',
		array('ip_address' => $ip));
	
	if($result === false) {
		echo '<strong>Error deleting logs</strong><br />';
		return false;
	}
	
	return true;
}

function um_db_create_component ($type, $name, $path = '', $binary = false, $relative_path = '') {
	$values = array('type' => $type, 
		'name' => $name, 
		'path' => $path, 
		'`binary`' => $binary, 
		'relative_path' => $relative_path);
	
	$result = db_process_sql_insert(DB_PREFIX.'tupdate_component', $values);
	
	if($result === false) {
		echo '<strong>Error creating component</strong><br />';
		return false;
	}
	
	return true;
}

function um_db_update_component ($name, $path = '', $binary = false, $relative_path = '') {
	$values = array ('path' => $path, 'binary' => $binary, 'relative_path' => $relative_path);
	$where = array ('name' => $name);
	
	$result = db_process_sql_update(DB_PREFIX.'tupdate_component', $values, $where);
	
	if($result === false) {
		echo '<strong>Error updating component</strong><br />';
		return false;
	}
	
	return true;
}

function um_db_delete_component ($name) {
	$result = db_process_sql_delete(DB_PREFIX.'tupdate_component', array('name' => $name));
	
	if($result === false) {
		echo '<strong>Error deleting component</strong><br />';
		return false;
	}
	
	return true;
}

function um_db_get_component ($name) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT COUNT(*)
				FROM '.DB_PREFIX.'tupdate_component
				WHERE name = "'.$name.'" LIMIT 1');
			break;
		case "postgresql":
			$result = db_process_sql('SELECT COUNT(*)
				FROM '.DB_PREFIX.'tupdate_component
				WHERE name = \''.$name.'\' LIMIT 1');
			break;
		case "oracle":
			$result = db_process_sql('SELECT COUNT(*)
				FROM '.DB_PREFIX.'tupdate_component
				WHERE name = \''.$name.'\' AND rownum < 2');
			break;
	}
	
	if ($result === false) {
		echo '<strong>Error getting component</strong> <br />';
		return array();
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT *
				FROM '.DB_PREFIX.'tupdate_component
				WHERE name = "'.$name.'" LIMIT 1');
			break;
		case "postgresql":
			$result = db_process_sql('SELECT *
				FROM '.DB_PREFIX.'tupdate_component
				WHERE name = \''.$name.'\' LIMIT 1');
			break;
		case "oracle":
			$result = db_process_sql('SELECT *
				FROM '.DB_PREFIX.'tupdate_component
				WHERE name = \''.$name.'\' AND rownum < 2');
			break;
	}
	
	$component = um_std_from_result($result);
	
	if ($component->relative_path != '') {
		$last = $component->relative_path[strlen ($component->relative_path) - 1];
		if ($last != '/')
			$component->relative_path .= '/';
	}
	
	return $component;
}

function um_db_get_all_components ($type = '') {
	if ($type != '') {
		$result = db_process_sql('SELECT *
			FROM '.DB_PREFIX.'tupdate_component WHERE type = '.$type);
	}
	else {
		$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate_component');
	}
	
	if ($result === false) {
		echo '<strong>Error getting component</strong> <br />';
		return array();
	}
	
	$cont = 0;
	$components = array();
	while(true) {
		$component = um_std_from_result($result, $cont);
		if($component === false) {
			break;
		}
		$components[$component->name] = $component;
		$cont++;
	}
	
	return $components;
}

function um_db_create_component_db ($table_name, $field_name, $order, $component_name) {
	$values = array('table_name' => $table_name,
		'field_name' => $field_name,
		'`order`' => $order,
		'component' => $component_name);
	$result = db_process_sql_insert(DB_PREFIX.'tupdate_component_db', $values);
	
	if ($result === false) {
		echo '<strong>Error creating database component</strong> <br />';
		
		return false;
	}
	
	return true;
}

function um_db_update_component_db ($id, $table_name = '', $field_name = '', $order = '') {
	$values = array ('table_name' => $table_name,
		'field_name' => $field_name,
		'`order`' => $order);
	$where = array ('id' => $id);
	
	$result = db_process_sql_update(DB_PREFIX.'tupdate_component_db', $values, $where);
	
	if($result === false) {
		echo '<strong>Error updating database component</strong><br />';
		
		return false;
	}
	
	return true;
}

function um_delete_directory($dirname) {
	if (is_dir($dirname))
		$dir_handle = opendir($dirname);
	if (!$dir_handle)
		return false;
	
	while ($file = readdir($dir_handle)) {
		if ($file != "." && $file != "..") {
			if (!is_dir($dirname."/".$file))
				unlink($dirname."/".$file);
			else
				um_delete_directory($dirname.'/'.$file);    
		}
	}
	closedir($dir_handle);
	rmdir($dirname);
	
	return true;
}

function um_db_delete_component_db ($id) {
	$result = db_process_sql_delete(DB_PREFIX.'tupdate_component_db',
		array('id' => $id));
	
	if($result === false) {
		echo '<strong>Error deleting database component</strong><br />';
		return false;
	}
	
	return true;
}

function um_db_get_component_db ($id_component_db) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT COUNT(*)
				FROM '.DB_PREFIX.'tupdate_component_db
				WHERE id = "'.$id_component_db.'" LIMIT 1');
			break;
		case "postgresql":
			$result = db_process_sql('SELECT COUNT(*)
				FROM '.DB_PREFIX.'tupdate_component_db
				WHERE id = \''.$id_component_db.'\' LIMIT 1');
			break;
		case "oracle":
			$result = db_process_sql('SELECT COUNT(*)
				FROM '.DB_PREFIX.'tupdate_component_db
				WHERE id = \''.$id_component_db.'\' AND rownum < 2');
			break;
	}
	
	if ($result === false) {
		echo '<strong>Error getting database component</strong> <br />';
		return NULL;
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT *
				FROM '.DB_PREFIX.'tupdate_component_db
				WHERE id = "'.$id_component_db.'" LIMIT 1');
			break;
		case "postgresql":
			$result = db_process_sql('SELECT *
				FROM '.DB_PREFIX.'tupdate_component_db
				WHERE id = \''.$id_component_db.'\' LIMIT 1');
			break;
		case "oracle":
			$result = db_process_sql('SELECT *
				FROM '.DB_PREFIX.'tupdate_component_db
				WHERE id = \''.$id_component_db.'\' AND rownum < 2');
			break;
	}
	
	$component = um_std_from_result($result);
	
	return $component;
}

function um_db_get_database_components ($component_name) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT COUNT(*)
				FROM '.DB_PREFIX.'tupdate_component_db
				WHERE component = "'. $component_name.'" ORDER BY `order` ASC');
			break;
		case "postgresql":
		case "oracle":
			$result = db_process_sql('SELECT COUNT(*)
				FROM '.DB_PREFIX.'tupdate_component_db
				WHERE component = \''. $component_name.'\' ORDER BY "order" ASC');
			break;
	}
	
	if ($result === false) {
		echo '<strong>Error getting database components </strong> <br />';
		return array();
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT *
				FROM '.DB_PREFIX.'tupdate_component_db
				WHERE component = "'. $component_name.'" ORDER BY `order` ASC');
			break;
		case "postgresql":
		case "oracle":
			$result = db_process_sql('SELECT *
				FROM '.DB_PREFIX.'tupdate_component_db
				WHERE component = \''. $component_name.'\' ORDER BY "order" ASC');
			break;
	}
	
	$cont = 0;
	$components = array();
	while (true) {
		$component = um_std_from_result($result, $cont);
		if($component === false) {
			break;
		}
		$components[$component->id] = $component;
		$cont++;
	}
	
	return $components;
}

function um_db_create_auth ($client_key, $subscription_limit, $description = '', $developer = false) {
	global $db;
	
	if (! is_numeric ($subscription_limit)) {
		echo '<strong>Error</strong>: Subscription must be numeric<br />';
		return false;
	}
	
	$values = array ('client_key' => $client_key,
		'subscription_limit' => $subscription_limit,
		'description' => $description,
		'developer' => $developer);
	$result = db_process_sql_insert(DB_PREFIX.'tupdate_auth', $values);
	
	if ($result === false) {
		echo '<strong>Error creating authorization</strong> <br />';
		return false;
	}
	
	return true;
}

function um_db_update_auth ($id_auth, $client_key, $subscription_limit, $description = '', $developer = false) {
	if (! is_numeric ($subscription_limit)) {
		echo '<strong>Error</strong>: Subscription must be numeric<br />';
		return false;
	}
	
	$values = array ('client_key' => $client_key, 
		'subscription_limit' => $subscription_limit, 
		'description' => $description, 
		'developer' => $developer);	
	$where = array ('id' => $id_auth);
	
	$result = db_process_sql_update(DB_PREFIX.'tupdate_auth', $values, $where);
	
	if ($result === false) {
		echo '<strong>Error updating authorization</strong><br />';
		return false;
	}
	
	return true;
}

function um_db_delete_auth ($id_auth) {
	$result = db_process_sql_delete(DB_PREFIX.'tupdate_auth',
		array('id' => $id_auth));
	
	if($result === false) {
		echo '<strong>Error deleting authorization</strong><br />';
		return false;
	}
	
	return true;
}

function um_db_get_auth ($id_auth) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate_auth WHERE id = "'.$id_auth.'" LIMIT 1');
			break;
		case "postgresql":
			$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate_auth WHERE id = \''.$id_auth.'\' LIMIT 1');
			break;
		case "oracle":
			$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate_auth WHERE id = \''.$id_auth.'\' AND rownum < 2');
			break;
	}
	
	if ($result === false) {
		echo '<strong>Error getting authorization</strong> <br />';
		return array();
	}

	$auth = um_std_from_result($result);

	return $auth;
}

function um_db_get_all_auths () {
	$result = db_process_sql('SELECT COUNT(*) FROM '.DB_PREFIX.'tupdate_auth');
	
	if ($result === false) {
		echo '<strong>Error getting authorizations</strong> <br />';
		return array();
	}
		
	$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate_auth');

	$cont = 0;
	$auths = array();
	while(true) {
		$auth = um_std_from_result($result, $cont);
		if($auth === false) {
			break;
		}
		$auths[$auth->id] = $auth;
		$cont++;
	}
	
	return $auths;
}

function um_db_check_auth ($client_key, $subscription_limit) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate_auth WHERE client_key = "'.$client_key.'" LIMIT 1');
			break;
		case "postgresql":
			$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate_auth WHERE client_key = \''.$client_key.'\' LIMIT 1');
			break;
		case "oracle":
			$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate_auth WHERE client_key = \''.$client_key.'\' AND rownum < 2');
			break;
	}
	
	if ($result === false) {
		echo '<strong>Error checking authorization</strong> <br />';
		return array();
	}
	
	$auth = um_std_from_result($result);
	
	if (!$auth) {
		return false;
	}
	
	if ($auth->developer == 1 || $auth->subscription_limit >= $subscription_limit) {
		return $auth->id;
	}
		
	return false;
}

function um_db_is_auth_developer ($id_auth) {
	$developer = db_get_value('developer', DB_PREFIX.'tupdate_auth', '`id`', $id_auth);

	if ($developer === false) {
		echo '<strong>Error reading authorization developers bit</strong> <br />';
		return false;
	}
	
	return (bool) $developer;
}

function um_db_connect ($backend = 'mysql', $host = '', $user = '', $password = '', $db_name = '', $port = null) {
	return db_connect ($host, $db_name, $user, $password, null, $port);
}

function um_component_db_connect () {
	$settings = um_db_load_settings ();
	
	return db_connect ($settings->dbhost, $settings->dbname, $settings->dbuser, $settings->dbpass, null, $settings->dbport);
}

function um_get_package_status () {
	$status = array ();
	
	$status['development'] = __('Development');
	$status['testing'] = __('Testing');
	$status['public'] = __('Public');
	$status['disabled'] = __('Disabled');
	
	return $status;
}

function um_get_component_types () {
	$types = array ();
	
	$types['database'] = __('Database');
	$types['directory'] = __('Code / binary directory');
	
	return $types;
}
?>
