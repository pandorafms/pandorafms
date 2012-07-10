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


function um_component_database_get_data ($component_db) {
	$db = um_component_db_connect ();
	
	if ($db === false) {
		return false;
	}

	$fields = um_component_database_get_table_fields ($component_db->table_name);
	
	$result = db_process_sql('SELECT COUNT(*) FROM '.$component_db->table_name);
	if ($result === false) {
		echo '<strong>Error getting table fields</strong> <br />';
		return NULL;
	}
	
	$result = db_process_sql('SELECT '.implode (',', $fields).' FROM '.$component_db->table_name);

	$cont = 0;
	$resultdata = array();
	$field = $component_db->field_name;

	while(true) {
		$data = um_std_from_result($result, $cont);
		if($data === false) {
			break;
		}
		$update = um_update_get_last_from_table_field_value ($component_db->component,
										$component_db->id,
										$data->$field);
		if ($update && $update->db_field_value == $data->$field)
			continue;
		$resultdata[] = $data;
		$cont++;
	}
	
	return $resultdata;
}

function um_component_database_get_all_tables () {
	global $config;
	
	$db = um_component_db_connect ();
	
	if ($db === false) {
		return array ();
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SHOW TABLES');
			break;
		case "postgresql":
			$result = db_process_sql('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\';');
			break;
		case "oracle":
			$result = db_process_sql('SELECT table_name FROM user_tables');
			break;
	}

	if ($result === false) {
		echo '<strong>Error getting tables</strong> <br />';
		return array();
	}
	
	$cont = 0;
	$tables = array();
	foreach($result as $table) {
		if ($config["dbtype"] == 'oracle') {
			$tables[] = $table['table_name'];
		}
		else {
			$tables[] = $table[0];
		}
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
	global $config;
	
	$db = um_component_db_connect ();
	
	if ($db === false) {
		return array ();
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SHOW COLUMNS FROM '.$table_name.' WHERE `Key` != "PRI"');
			break;
		case "postgresql":
			//TODO: verificar que se extraen todos los campos menos clave primaria
			$result = db_process_sql("SELECT * FROM pg_indexes WHERE tablename = '" . $table_name . "'");
			break;
		case "oracle":
			$result = db_process_sql("SELECT cols1.column_name as Fields, cols1.data_type as Type,
				CASE WHEN (cols1.nullable = 'Y') THEN 'YES' ELSE 'NO' END as \"Null\", 
				cols1.data_default as \"Default\", '' as Extra
				FROM user_tab_columns cols1 
				WHERE cols1.table_name ='".$table_name."' 
				AND cols1.column_name NOT IN (select distinct usr.column_name 
					from user_cons_columns usr, user_constraints co 
					where usr.constraint_name = co.constraint_name and 
					constraint_type = 'P' and co.table_name = '" . $table_name . "') 
				order by cols1.column_id");
			break;
	}
	
	if ($result === false) {
		echo '<strong>Error getting table fields</strong> <br />';
		return array();
	}

	$cont = 0;
	$fields = array();
	while(true) {
		$field = um_std_from_result($result, $cont);
		if($field === false) {
			break;
		}
		$fields[$cont] = $field->Field;
		$cont++;
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
					array ('.svn', '.cvs', '.git', '.', '..'), $binary);
	$blacklisted = um_component_get_all_blacklisted ($component);
	
	return (array_diff ($files, $blacklisted));
}

function um_component_directory_get_modified_files ($component, $binary = false) {
	$all_files = um_component_directory_get_all_files ($component, $binary);
	
	$files = array ();
	foreach ($all_files as $file) {
		if (um_component_is_blacklisted ($component, $file))
			continue;
		
		$last_update = um_update_get_last_from_filename ($component->name,
			$file);
		if ($last_update) {
			$checksum = md5_file (realpath ($component->path.'/'.$file));
			if ($last_update->checksum == $checksum)
				continue;
		}
		array_push ($files, $file);
	}
	
	return $files;
}

function um_component_get_all_blacklisted ($component) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT COUNT(name) FROM '.DB_PREFIX.'tupdate_component_blacklist WHERE component = "'.$component->name.'"');
			break;
		case "postgresql":
		case "oracle":
			$result = db_process_sql('SELECT COUNT(name)
				FROM '.DB_PREFIX.'tupdate_component_blacklist
				WHERE component = \''.$component->name.'\'');
			break;
	}

	if ($result === false) {
		echo '<strong>Error getting all blacklisted items</strong> <br />';
		return array();
	}
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT name FROM '.DB_PREFIX.'tupdate_component_blacklist WHERE component = "'.$component->name.'"');
			break;
		case "postgresql":
		case "oracle":
			$result = db_process_sql('SELECT name
			FROM '.DB_PREFIX.'tupdate_component_blacklist
			WHERE component = \''.$component->name.'\'');
			break;
	}
	
	$cont = 0;
	$list = array();
	while(true) {
		$element = um_std_from_result($result, $cont);
		if($element === false) {
			break;
		}
		$list[$cont] = $element->name;
		$cont++;
	}
	
	return $list;
}

function um_component_is_blacklisted ($component, $name) {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			$result = db_process_sql('SELECT COUNT(*) AS blacklisted FROM '.DB_PREFIX.'tupdate_component_blacklist WHERE component = "'.$component->name.'" AND name = "'.$name.'"');
			break;
		case "postgresql":
		case "oracle":
			$result = db_process_sql('SELECT COUNT(*) AS blacklisted
				FROM '.DB_PREFIX.'tupdate_component_blacklist
				WHERE component = \''.$component->name.'\' AND name = \''.$name.'\'');
			break;
	}

	if ($result === false) {
		echo '<strong>Error getting blacklist item</strong> <br />';
		return false;
	}
	
	$retval = um_std_from_result($result);
	
	return $retval->blacklisted ? true : false;
}

function um_component_add_blacklist ($component, $name) {
	$values = array('component' => $component->name, 'name' => $name);
	$result = db_process_sql_insert(DB_PREFIX.'tupdate_component_blacklist', $values);
	
	if ($result === false) {
		echo '<strong>Error creating blacklist component</strong> <br />';
		return false;
	}
	
	return true;
}
?>
