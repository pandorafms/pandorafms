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


function um_update_get_last_from_filename ($component_name, $filename) {
	global $db;
	
	$values = array ($component_name, $filename);
	$sql =& $db->prepare ('SELECT * FROM tupdate WHERE component = ? AND filename = ? ORDER BY id DESC LIMIT 1');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$result->fetchInto ($update);
	return $update;
}

function um_update_get_last_from_table_field_value ($component_name, $id_component_db, $field_value) {
	global $db;
	
	$values = array ($component_name, $id_component_db, $field_value);
	$sql =& $db->prepare ('SELECT * FROM tupdate WHERE component = ? AND id_component_db = ? AND db_field_value = ? ORDER BY id DESC LIMIT 1');
	$result =& $db->execute ($sql, $values);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$result->fetchInto ($update);
	return $update;
}

function um_db_get_orphan_updates () {
	global $db;
	
	$result =& $db->query ('SELECT * FROM tupdate WHERE id_update_package IS NULL');
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$updates = array ();
	while ($result->fetchInto ($update)) {
		$updates[$update['id']] = $update;
	}
	return $updates;
}

function um_db_get_update ($id_update) {
	global $db;
	
	$sql =& $db->prepare ('SELECT * FROM tupdate WHERE id = ? LIMIT 1');
	$result =& $db->execute ($sql, $id_update);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$result->fetchInto ($update);
	
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
	
	$sql =& $db->prepare ('DELETE FROM tupdate WHERE id = ?');
	$result =& $db->execute ($sql, $id_update);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return false;
	}
	return true;
}

function um_db_create_update ($type, $component_name, $id_package, $update, $db_data = NULL) {
	global $db;
	
	if ($id_package == 0)
		return false;
	$component = um_db_get_component ($component_name);
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
		$values['filename'] = $update->filename;
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
		$values['data'] = 'INSERT INTO `'.$component_db->table_name.'` (`'.implode('`,`', array_keys (get_object_vars ($db_data))).'`) VALUES (\''.implode('\',\'', get_object_vars ($db_data)).'\')';
		
		break;
	case 'db_schema':
		$values['data'] = $update->data;
		
		break;
	default:
		return false;
	}
	$replace = array ();
	for ($i = 0; $i < sizeof ($values); $i++) {
		$replace[] = '?';
	}
	$sql =& $db->prepare ('INSERT INTO tupdate ('.implode(',', array_keys ($values)).') VALUES ('.implode(',', $replace).')');
	$result =& $db->execute ($sql, array_values ($values));
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
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
	return convert_uuencode ($content);
}
?>
