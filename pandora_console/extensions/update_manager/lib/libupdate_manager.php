<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


/* PEAR DB manage abstraction */
$prev_level = error_reporting (0);
if ((include_once ('DB.php')) != 1)
	die ('<p>PEAR::DB not found. Please install it with: <pre>pear install DB</pre></p>');
error_reporting ($prev_level);
unset ($prev_level);

require_once ('libupdate_manager_utils.php');
require_once ('libupdate_manager_updates.php');
require_once ('libupdate_manager_components.php');
require_once ('libupdate_manager_client.php');

function um_db_load_settings () {
	global $db;
	
	$result =& $db->query ('SELECT * FROM tupdate_settings');
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$settings = new stdClass ();
	while ($result->fetchInto ($setting)) {
		$key = $setting->key;
		$settings->$key = $setting->value;
	}
	
	return $settings;
}

function um_db_update_setting ($key, $value = '') {
	global $db;
	
	$sql =& $db->prepare ('SELECT COUNT(*) e FROM tupdate_settings WHERE `key` = ?');
	$result =& $db->execute ($sql, $key);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	
	$result->fetchInto ($exists);
	$values = array ($value, $key);
	if ($exists->e) {
		$sql =& $db->prepare ('UPDATE tupdate_settings SET value = ? WHERE `key` = ?');
		$result =& $db->execute ($sql, $values);
		if (PEAR::isError ($result)) {
			echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
			return false;
		}
	} else {
		$sql =& $db->prepare ('INSERT INTO tupdate_settings (value, `key`) VALUES (?, ?)');
		$result =& $db->execute ($sql, $values);
		if (PEAR::isError ($result)) {
			echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
			return false;
		}
	}
	return true;
}

function um_db_get_latest_public_package ($id_package = '0') {
	global $db;
	
	$values = array ('public', $id_package);
	$sql =& $db->prepare ('SELECT * FROM tupdate_package WHERE status = ? AND id > ? ORDER BY id DESC LIMIT 1');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	$result->fetchInto ($package);
	
	return $package;
}

function um_db_get_latest_development_package ($id_package = '0') {
	global $db;
	
	$values = array ('development', $id_package);
	$sql =& $db->prepare ('SELECT * FROM tupdate_package WHERE status = ? AND id > ? ORDER BY id DESC LIMIT 1');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	$result->fetchInto ($package);
	
	return $package;
}

function um_db_get_next_package ($id_package = '0', $development = false) {
	global $db;
	
	$values = array ('public', $id_package);
	$sql =& $db->prepare ('SELECT * FROM tupdate_package WHERE status = ? AND id > ? ORDER BY id ASC LIMIT 1');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	$result->fetchInto ($package);
	
	if (! $package && $development) {
		$values = array ('development', $id_package);
		$sql =& $db->prepare ('SELECT * FROM tupdate_package WHERE status = ? AND id > ? ORDER BY id ASC LIMIT 1');
		$result =& $db->execute ($sql, $values);
		if (PEAR::isError ($result)) {
			echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
			return false;
		}
		$result->fetchInto ($package);
	}
	
	return $package;
}

function um_db_create_package ($description = '') {
	global $db;
	
	$sql =& $db->prepare ('INSERT INTO tupdate_package (description) VALUES (?)');
	$result =& $db->execute ($sql, $description);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	return true;
}

function um_db_update_package ($id_package, $description = '', $status = 'disabled') {
	global $db;
	
	$values = array ($description, $status, $id_package);
	
	$sql =& $db->prepare ('UPDATE tupdate_package SET description = ?, status = ? WHERE id = ?');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	return true;
}

function um_db_delete_package ($id_package) {
	global $db;
	
	$package = um_db_get_package ($id_package);
	if ($package->status != 'development') {
		echo '<strong>Error</strong>: '.'Only packages in development state can be deleted';
		return false;
	}
	
	$sql =& $db->prepare ('DELETE FROM tupdate_package WHERE id = ?');
	$result =& $db->execute ($sql, $id_package);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	return true;
}

function um_db_get_package ($id_package) {
	global $db;
	
	$sql =& $db->prepare ('SELECT * FROM tupdate_package WHERE id = ? LIMIT 1');
	$result =& $db->execute ($sql, $id_package);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$result->fetchInto ($package);
	
	return $package;
}

function um_db_get_all_packages () {
	global $db;
	
	$result =& $db->query ('SELECT * FROM tupdate_package');
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return array ();
	}
	$packages = array ();
	while ($result->fetchInto ($package)) {
		$packages[$package->id] = $package;
	}
	
	return $packages;
}

function um_db_get_package_updates ($id_package) {
	global $db;
	
	$sql =& $db->prepare ('SELECT * FROM tupdate WHERE id_update_package = ?');
	$result =& $db->execute ($sql, $id_package);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$updates = array ();
	while ($result->fetchInto ($update)) {
		$updates[$update->id] = $update;
	}
	
	return $updates;
}

function um_db_create_package_log ($id_package, $client_key, $user_package, $result = 'query', $user_subscription = '', $description = '') {
	global $db;
	
	$values = array ($id_package, $client_key, $_SERVER['REMOTE_ADDR'], $user_package, $user_subscription, $result, $description);
	$sql =& $db->prepare ('INSERT INTO tupdate_package_log (id_update_package, client_key, ip_address, user_package, user_subscription, result, description) VALUES (?, ?, ?, ?, ?, ?, ?)');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		return false;
	}
	return true;
}

function um_db_get_all_package_logs () {
	global $db;
	
	$result =& $db->query ('SELECT * FROM tupdate_package_log ORDER BY `timestamp` DESC');
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return array ();
	}
	$logs = array ();
	while ($result->fetchInto ($log)) {
		$logs[$log->id] = $log;
	}
	
	return $logs;
}

function um_db_create_component ($type, $name, $path = '') {
	global $db;
	
	$values = array ($type, $name, $path);
	$sql =& $db->prepare ('INSERT INTO tupdate_component (type, name, path) VALUES (?, ?, ?)');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	return true;
}

function um_db_update_component ($name, $path = '') {
	global $db;
	
	$values = array ($path, $name);
	
	$sql =& $db->prepare ('UPDATE tupdate_component SET path = ? WHERE name = ?');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	return true;
}

function um_db_delete_component ($name) {
	global $db;
	
	$sql =& $db->prepare ('DELETE FROM tupdate_component WHERE name = ?');
	$result =& $db->execute ($sql, $name);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	return true;
}

function um_db_get_component ($name) {
	global $db;
	
	$sql =& $db->prepare ('SELECT * FROM tupdate_component WHERE name = ? LIMIT 1');
	$result =& $db->execute ($sql, $name);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$result->fetchInto ($component);
	
	return $component;
}

function um_db_get_all_components ($type = '') {
	global $db;
	
	if ($type != '') {
		$sql =& $db->prepare ('SELECT * FROM tupdate_component WHERE type = ?');
		$result =& $db->execute ($sql, $type);
	} else {
		$result =& $db->query ('SELECT * FROM tupdate_component');
	}
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return array ();
	}
	$components = array ();
	while ($result->fetchInto ($component)) {
		$components[$component->name] = $component;
	}
	
	return $components;
}

function um_db_create_component_db ($table_name, $field_name, $order, $component_name) {
	global $db;
	
	$values = array ($table_name, $field_name, $order, $component_name);
	$sql =& $db->prepare ('INSERT INTO tupdate_component_db (table_name, field_name, `order`, component) VALUES (?, ?, ?, ?)');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	return true;
}

function um_db_update_component_db ($id, $table_name = '', $field_name = '', $order = '') {
	global $db;
	
	$values = array ($table_name, $field_name, $order, $id);
	$sql =& $db->prepare ('UPDATE tupdate_component_db SET table_name = ?, field_name = ?, `order` = ? WHERE id = ?');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	return true;
}

function um_db_delete_component_db ($id) {
	global $db;
	
	$sql =& $db->prepare ('DELETE FROM tupdate_component_db WHERE id = ?');
	$result =& $db->execute ($sql, $id);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	return true;
}

function um_db_get_component_db ($id_component_db) {
	global $db;
	
	$sql =& $db->prepare ('SELECT * FROM tupdate_component_db WHERE id = ? LIMIT 1');
	$result =& $db->execute ($sql, $id_component_db);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$result->fetchInto ($component);
	
	return $component;
}

function um_db_get_database_components ($component_name) {
	global $db;
	
	$sql =& $db->prepare ('SELECT * FROM tupdate_component_db WHERE component = ? ORDER BY `order` ASC');
	$result =& $db->execute ($sql, $component_name);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$components = array ();
	while ($result->fetchInto ($component)) {
		$components[$component->id] = $component;
	}
	
	return $components;
}

function um_db_create_auth ($client_key, $subscription_limit, $description = '', $developer = false) {
	global $db;
	
	if (! is_numeric ($subscription_limit)) {
		echo '<strong>Error</strong>: Subscription must be numeric<br />';
		return false;
	}
	$values = array ($client_key, $subscription_limit, $description, $developer);
	$sql =& $db->prepare ('INSERT INTO tupdate_auth (client_key, subscription_limit, description, developer) VALUES (?, ?, ?, ?)');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	return true;
}

function um_db_update_auth ($id_auth, $client_key, $subscription_limit, $description = '', $developer = false) {
	global $db;
	
	if (! is_numeric ($subscription_limit)) {
		echo '<strong>Error</strong>: Subscription must be numeric<br />';
		return false;
	}
	$values = array ($client_key, $subscription_limit, $description,
			$developer, $id_auth);
	$sql =& $db->prepare ('UPDATE tupdate_auth SET client_key = ?, subscription_limit = ?, description = ?, developer = ? WHERE id = ?');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	return true;
}

function um_db_delete_auth ($id_auth) {
	global $db;
	
	$sql =& $db->prepare ('DELETE FROM tupdate_auth WHERE id = ?');
	$result =& $db->execute ($sql, $id_auth);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	
	return true;
}

function um_db_get_auth ($id_auth) {
	global $db;
	
	$sql =& $db->prepare ('SELECT * FROM tupdate_auth WHERE id = ? LIMIT 1');
	$result =& $db->execute ($sql, $id_auth);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$result->fetchInto ($auth);
	
	return $auth;
}

function um_db_get_all_auths () {
	global $db;
	
	$result =& $db->query ('SELECT * FROM tupdate_auth');
	
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return array ();
	}
	$auths = array ();
	while ($result->fetchInto ($auth)) {
		$auths[$auth->id] = $auth;
	}
	
	return $auths;
}

function um_db_check_auth ($client_key, $subscription_limit) {
	global $db;
	
	$sql =& $db->prepare ('SELECT * FROM tupdate_auth WHERE client_key = ? LIMIT 1');
	$result =& $db->execute ($sql, $client_key);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	$result->fetchInto ($auth);
	if (! $auth)
		return false;
	
	if ($auth->developer == 1)
		return $auth->id;
	
	if ($auth->subscription_limit >= $subscription_limit)
		return $auth->id;
	return false;
}

function um_db_is_auth_developer ($id_auth) {
	global $db;
	
	$developer =& $db->getOne ('SELECT developer FROM tupdate_auth WHERE id = ? LIMIT 1', $id_auth);
	if (PEAR::isError ($developer)) {
		echo '<strong>Error</strong>: '.$developer->getMessage ().'<br />';
		return false;
	}
	return (bool) $developer;
}

function um_db_connect ($backend = 'mysql', $host = '', $user = '', $password = '', $db_name = '') {
	static $db = NULL;
	
	if ($db)
		return $db;
	
	$dsn = $backend.'://'.$user.':'.$password.'@'.$host.'/'.$db_name;
	$db =& DB::Connect ($dsn, array ());
	if (PEAR::isError ($db)) {
		echo '<strong>Error</strong>: '.$db->getMessage ().'<br />';
		die;
	}
	$db->setFetchMode (DB_FETCHMODE_OBJECT);
	
	return $db;
}

function um_component_db_connect () {
	$settings = um_db_load_settings ();
	
	$dsn = 'mysql://'.$settings->dbuser.':'.$settings->dbpass.'@'.$settings->dbhost.'/'.$settings->dbname;
	$db =& DB::Connect ($dsn, array ());
	if (PEAR::isError ($db)) {
		return false;
	}
	$db->setFetchMode (DB_FETCHMODE_ORDERED);
	
	return $db;
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
