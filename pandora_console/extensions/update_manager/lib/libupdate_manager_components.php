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


function um_component_database_get_data ($component_db) {
	$db = um_component_db_connect ();
	
	if ($db === false) {
		return false;
	}
	$db->setFetchMode (DB_FETCHMODE_OBJECT);
	
	$fields = um_component_database_get_table_fields ($component_db->table_name);
	$sql =& $db->prepare ('SELECT '.implode (',', $fields).' FROM !');
	$result =& $db->execute ($sql, $component_db->table_name);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return NULL;
	}
	$resultdata = array ();
	$field = $component_db->field_name;
	while ($result->fetchInto ($data)) {
		$update = um_update_get_last_from_table_field_value ($component_db->component,
								$component_db->id,
								$data->$field);
		if ($update && $update->db_field_value == $data->$field)
			continue;
		array_push ($resultdata, $data);
	}
	
	return $resultdata;
}

function um_component_database_get_all_tables () {
	$db = um_component_db_connect ();
	
	if ($db === false) {
		return array ();
	}
	
	$result =& $db->query ('SHOW TABLES');
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return array ();
	}
	$tables = array ();
	while ($result->fetchInto ($table)) {
		array_push ($tables, $table[0]);
	}
	
	return $tables;
}

function um_component_database_get_available_tables ($component_name) {
	$all_tables = um_component_database_get_all_tables ();
	$components_db = um_db_get_database_components ($component_name);
	$defined_tables = array ();
	foreach ($components_db as $component_db) {
		array_push ($defined_tables, $component_db->table_name);
	}
	
	return array_diff ($all_tables, $defined_tables);
}

function um_component_database_get_table_fields ($table_name) {
	$db = um_component_db_connect ();
	
	if ($db === false) {
		return array ();
	}
	
	$sql =& $db->prepare ('SHOW COLUMNS FROM ! WHERE `Key` \!= "PRI"');
	$result =& $db->execute ($sql, $table_name);
	if (PEAR::isError ($result)) {
		echo '<strong>Error</strong>: '.$result->getMessage ().'<br />';
		return array ();
	}
	
	$fields = array ();
	while ($result->fetchInto ($field)) {
		array_push ($fields, $field[0]);
	}
	return $fields;
}

function um_component_directory_get_all_files ($component, $binary = false) {
	if (! $component || ! isset ($component->path)) {
		return array ();
	}
	
	if (! is_dir ($component->path)) {
		return array ();
	}
	
	$path = $component->path;
	if (substr ($path, -1) != '/')
		$path .= "/";
	$files = directory_to_array ($path,
					array ('.svn', '.cvs', '.git', '.', '..' ), $binary);
	
	return $files;
}

function um_component_directory_get_modified_files ($component, $binary = false) {
	$all_files = um_component_directory_get_all_files ($component, $binary);
	
	$files = array ();
	foreach ($all_files as $file) {
		$last_update = um_update_get_last_from_filename ($component->name, $file);
		if ($last_update) {
			$checksum = md5_file (realpath ($component->path.'/'.$file));
			if ($last_update->checksum == $checksum)
				continue;
		}
		
		array_push ($files, $file);
	}
	
	return $files;
}
?>
