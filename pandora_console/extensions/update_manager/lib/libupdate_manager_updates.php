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



function um_update_get_last_from_filename ($component_name, $filename) {
	global $config;
	
	$component = um_db_get_component ($component_name);
	
	if (! $component)
		return;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT COUNT(*) FROM '.DB_PREFIX.'tupdate WHERE component = "'.$component_name.'" AND filename = "'.$component->relative_path.$filename.'" ORDER BY id DESC LIMIT 1');
			break;
		case "postgresql":
			$result = db_process_sql('SELECT COUNT(*) FROM '.DB_PREFIX.'tupdate WHERE component = \''.$component_name.'\' AND filename = \''.$component->relative_path.$filename.'\' ORDER BY id DESC LIMIT 1');
			break;
		case "oracle":
			$result = db_process_sql('SELECT COUNT(*) FROM '.DB_PREFIX.'tupdate WHERE (component = \''.$component_name.'\' AND filename = \''.$component->relative_path.$filename.'\') AND rownum < 2 ORDER BY id DESC');
			break;
	}

	if ($result === false) {
		echo '<strong>Error getting update from filename</strong> <br />';
		return NULL;
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate WHERE component = "'.$component_name.'" AND filename = "'.$component->relative_path.$filename.'" ORDER BY id DESC LIMIT 1');
			break;
		case "postgresql":
			$result = db_process_sql('SELECT *
				FROM '.DB_PREFIX.'tupdate
				WHERE component = \''.$component_name.'\'
					AND filename = \''.$component->relative_path.$filename.'\' ORDER BY id DESC LIMIT 1');
			break;
		case "oracle":
			$result = db_process_sql('SELECT *
				FROM '.DB_PREFIX.'tupdate
				WHERE (component = \''.$component_name.'\'
					AND filename = \''.$component->relative_path.$filename.'\') AND rownum < 2 ORDER BY id DESC');
			break;
	}

	$update = um_std_from_result($result);

	return $update;
}

function um_update_get_last_from_table_field_value ($component_name, $id_component_db, $field_value) {
	$result = db_process_sql('SELECT COUNT(*) FROM '.DB_PREFIX.'tupdate WHERE component = "'.$component_name.'" AND id_component_db = "'.$id_component_db.'" AND db_field_value = "'.$field_value.'" ORDER BY id DESC LIMIT 1');

	if ($result === false) {
		echo '<strong>Error getting last value</strong> <br />';
		return NULL;
	}
		
	$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate WHERE component = "'.$component_name.'" AND id_component_db = "'.$id_component_db.'" AND db_field_value = "'.$field_value.'" ORDER BY id DESC LIMIT 1');
		
	$update = um_std_from_result($result);

	return $update;
}

function um_db_get_orphan_updates () {
	$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate WHERE id_update_package IS NULL');

	if ($result === false) {
		echo '<strong>Error getting orphan updates</strong> <br />';
		return NULL;
	}

	$cont = 0;
	$updates = array();
	while(true) {
		$update = um_std_from_result($result, $cont);
		if($update === false) {
			break;
		}
		$updates[$update['id']] = $update;
		$cont++;
	}
	
	return $updates;
}

function um_db_get_update ($id_update) {
	$result = db_process_sql('SELECT * FROM '.DB_PREFIX.'tupdate WHERE id = "'.$id_update.'" LIMIT 1');

	if ($result === false) {
		echo '<strong>Error getting update</strong> <br />';
		return NULL;
	}
	
	$update = um_std_from_result($result);
	
	return $update;
}

function um_db_delete_update ($id_update) {
	global $db;
	
	$update = um_db_get_update ($id_update);
	$package = um_db_get_package ($update->id_update_package);
	if ($package->status != 'development') {
		echo '<strong>Error</strong>: '.'Only packages in development state can be deleted';
		return false;
	}
	$result = db_process_sql_delete(DB_PREFIX.'tupdate', array('id' => $id_update));

	if ($result === false) {
		echo '<strong>Error deleting update</strong> <br />';
		return false;
	}

	return true;
}

function um_db_create_update ($type, $component_name, $id_package, $update, $db_data = NULL) {
	global $db;
	global $config;
	
	if ($id_package == 0)
		return false;
	$component = um_db_get_component ($component_name);
	if (! $component)
		return;
	$values = array ('type' => $type,
					'component' => $component_name,
					'id_update_package' => $id_package);
	switch ($type) {
		case 'code':
			$filepath = realpath ($component->path.'/'.$update->filename);
			$values['svn_version'] = um_file_get_svn_revision ($filepath);
		case 'binary':
			$last_update = um_update_get_last_from_filename ($component_name, $update->filename);
			$filepath = realpath ($component->path.'/'.$update->filename);
			$values['checksum'] = md5_file ($filepath);
			if ($last_update && $last_update->checksum == $values['checksum']) {
				return false;
			}
			
			/* Add relative path if has one */
			if ($component->relative_path != '') {
				$values['filename'] = $component->relative_path.$update->filename;
			}
			else {
				$values['filename'] = $update->filename;
			}
			$values['data'] = um_file_uuencode ($filepath);
			if ($last_update && $last_update->checksum != '')
				$values['previous_checksum'] = $last_update->checksum;
			
			break;
		case 'db_data':
			if ($db_data === NULL)
				return false;
			$component_db = um_db_get_component_db ($update->id_component_db);
			$field = $component_db->field_name;
			$values['db_field_value'] = $db_data->$field;
			$values['id_component_db'] = $update->id_component_db;
			switch ($config["dbtype"]) {
				case "mysql":
					$values['data'] = um_data_encode('INSERT INTO `'.$component_db->table_name.'` (`'.implode('`,`', array_keys (get_object_vars ($db_data))).'`) VALUES (\''.implode('\',\'', get_object_vars ($db_data)).'\')');
					break;
				case "postgresql":
					$values['data'] = um_data_encode('INSERT INTO "'.$component_db->table_name.'" ("'.implode('", "', array_keys (get_object_vars ($db_data))).'") VALUES (\''.implode('\',\'', get_object_vars ($db_data)).'\')');
					break;
				case "oracle":
					$values['data'] = um_data_encode('INSERT INTO '.$component_db->table_name.' ('.implode(', ', array_keys (get_object_vars ($db_data))).') VALUES (\''.implode('\',\'', get_object_vars ($db_data)).'\')');
					break;
			}
			break;
		case 'db_schema':
			$values['data'] = um_data_encode($update->data);
			break;
		default:
			return false;
			break;
	}
	
	$result = db_process_sql_insert(DB_PREFIX.'tupdate', $values);
	
	if ($result === false) {
		echo '<strong>Error creating update</strong> <br />';
		return false;
	}
	
	return true;
}

function um_get_update_types () {
	$types = array ();
	
	$types['code'] = 'Code';
	$types['db_data'] = 'Database data';
	$types['db_schema'] = 'Database schema';
	$types['binary'] = 'Binary file';
	
	return $types;
}

function um_file_get_svn_revision ($file) {
	return (int) exec ('svn info '.$file.'| grep "Revis" | head -1 | cut -f2 -d":"');
}

function um_file_uuencode ($file) {
	$content = file_get_contents ($file);
	return um_data_encode ($content);
}

function um_data_decode ($data) {
	return convert_uudecode(base64_decode($data));
}

function um_data_encode ($data) {
	return base64_encode(convert_uuencode ($data));
}
?>
